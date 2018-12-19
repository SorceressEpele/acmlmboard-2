<?php
  /* forum.php *************************************
  Changelog
0221  blackhole89    modified queries and $status calculation to use the new "threads read" system
  */
  require 'lib/common.php';


  if(isset($_GET['page'])) $page=$_GET['page'];
  if(!isset($page))
    $page=1;


  if($fid=$_GET['id']){
    checknumeric($fid);

    if($log){
      $forum=$sql->fetchq("SELECT f.*, r.time rtime FROM forums f "
                         ."LEFT JOIN forumsread r ON (r.fid=f.id AND r.uid=$loguser[id]) "
                         ."WHERE f.id=$fid AND f.id IN ".forums_with_view_perm());
      if(!$forum['rtime'])
        $forum['rtime']=0;
    }else
      $forum=$sql->fetchq("SELECT * FROM forums WHERE id=$fid AND id IN ".forums_with_view_perm());


    if (!isset($forum['id'])) {
      error("Error", "Forum does not exist.<br> <a href=./>Back to main</a>");      
    }

    //load tags
    $tags=array();
    $t=$sql->query("SELECT * FROM tags WHERE fid=$fid");
    while($tt=$sql->fetch($t)) $tags[]=$tt;

    $feedicons.=feedicon("img/rss2.png","rss.php?forum=$fid","RSS feed for this section");

    //append the forum's title to the site title
    pageheader($forum['title'],$fid);

    //forum access control // 2007-02-19 blackhole89 // 2011-11-09 blackhole89 tokenisation (more than 4.5 years...)
    //2012-01-01 DJBouche Happy New Year!

//[KAWA] Copypasting a chunk from ABXD, with some edits to make it work here.
$isIgnored = $sql->resultq("select count(*) from ignoredforums where uid=".$loguser['id']." and fid=".$fid) == 1;
if(isset($_GET['ignore']))
{
  check_token($_GET['auth'], "xign");
  if(!$isIgnored && $loguser['id']!=0)
  {
    $sql->query("insert into ignoredforums values (".$loguser['id'].", ".$fid.")");
    $isIgnored = true;
        print
        "$L[TBL1]>
".      "  $L[TR2]>
".      "    $L[TD1c]>
".      "      Forum ignored. You will no longer see any \"New\" markers for this forum.
".      "$L[TBLend]
";
  }
}
else if(isset($_GET['unignore']))
{
  check_token($_GET['auth'], "xign");
  if($isIgnored)
  {
    $sql->query("delete from ignoredforums where uid=".$loguser['id']." and fid=".$fid);
    $isIgnored = false;
        print
        "$L[TBL1]>
".      "  $L[TR2]>
".      "    $L[TD1c]>
".      "      Forum unignored.
".      "$L[TBLend]
";
  }
}

$editforumlink = "";

if (has_perm('edit-forums')) {
    $editforumlink = "<a href=\"manageforums.php?fid=$fid\" class=\"editforum\">Edit Forum</a> | ";
}

if($loguser['id']!=0){
	$auth = auth_url("xign");
	$ignoreLink = $isIgnored ? "<a href=\"forum.php?id=$fid&amp;unignore$auth\" class=\"unignoreforum\">Unignore forum</a> "
				: "<a href=\"forum.php?id=$fid&amp;ignore$auth\" class=\"ignoreforum\">Ignore forum</a> ";
}
    $threads=$sql->query("SELECT ".userfields('u1','u1').",".userfields('u2','u2').", t.*, 

    (SELECT COUNT(*) FROM threadthumbs WHERE tid=t.id) AS thumbcount,

    (NOT ISNULL(p.id)) ispoll".($log?", (NOT (r.time<t.lastdate OR isnull(r.time)) OR t.lastdate<'$forum[rtime]') isread":'').' '
                        ."FROM threads t "
                        ."LEFT JOIN users u1 ON u1.id=t.user "
                        ."LEFT JOIN users u2 ON u2.id=t.lastuser "
                        ."LEFT JOIN polls p ON p.id=t.id "
                  .($log?"LEFT JOIN threadsread r ON (r.tid=t.id AND r.uid=$loguser[id])":'')
                        ."WHERE t.forum=$fid AND t.announce=0 "
                        ."ORDER BY t.sticky DESC, t.lastdate DESC "
                        ."LIMIT ".(($page-1)*$loguser['tpp']).",".$loguser['tpp']);
    $topbot=
        "$L[TBL] width=100%>
".      "  $L[TDn]><a href=./>Main</a> - <a href=forum.php?id=$fid>$forum[title]</a></td>
".      "  $L[TDnr]>".$editforumlink.$ignoreLink.(can_create_forum_thread($forum)?"| <a href=\"newthread.php?id=$fid\" class=\"newthread\">New thread</a> | <a href=\"newthread.php?id=$fid&ispoll=1\" class=\"newpoll\">New poll</a>":"")."</td>
".      "$L[TBLend]
";
  }elseif($uid=$_GET[user]){
    checknumeric($uid);
    $user=$sql->fetchq("SELECT * FROM users WHERE id=$uid");

    pageheader("Threads by ".($user[displayname] ? $user[displayname] : $user[name]));

    $threads=$sql->query("SELECT ".userfields('u1','u1').",".userfields('u2','u2').", t.*, f.id fid, f.title ftitle, 
    (SELECT COUNT(*) FROM threadthumbs WHERE tid=t.id) AS thumbcount,


    (NOT ISNULL(p.id)) ispoll".($log?", (NOT (r.time<t.lastdate OR isnull(r.time)) OR t.lastdate<fr.time) isread":'').' '
                        ."FROM threads t "
                        ."LEFT JOIN users u1 ON u1.id=t.user "
                        ."LEFT JOIN users u2 ON u2.id=t.lastuser "
                        ."LEFT JOIN polls p ON p.id=t.id "
                        ."LEFT JOIN forums f ON f.id=t.forum "
                  .($log?"LEFT JOIN threadsread r ON (r.tid=t.id AND r.uid=$loguser[id]) "
            ."LEFT JOIN forumsread fr ON (fr.fid=f.id AND fr.uid=$loguser[id]) ":'')
                        ."LEFT JOIN categories c ON f.cat=c.id "
                        ."WHERE t.user=$uid "
                        .  "AND f.id IN ".forums_with_view_perm()." "
                        ."ORDER BY t.sticky DESC, t.lastdate DESC "
                        ."LIMIT ".(($page-1)*$loguser[tpp]).",".$loguser[tpp]);

    $forum[threads]=$sql->resultq("SELECT count(*) "
                                 ."FROM threads t "
                                 ."LEFT JOIN forums f ON f.id=t.forum "
                                 ."LEFT JOIN categories c ON f.cat=c.id "
                                 ."WHERE t.user=$uid "
                                 .  "AND f.id IN ".forums_with_view_perm()." ");
    $topbot=
        "$L[TBL] width=100%>
".      "  $L[TDn]><a href=./>Main</a> - Threads by ".($user[displayname] ? $user[displayname] : $user[name])."</td>
".      "$L[TBLend]
";
  }elseif($time=$_GET[time]){
    checknumeric($time);
    $mintime=ctime()-$time;

    pageheader('Latest posts');

    $threads=$sql->query("SELECT ".userfields('u1','u1').",".userfields('u2','u2').", t.*, f.id fid, 
    (SELECT COUNT(*) FROM threadthumbs WHERE tid=t.id) AS thumbcount,


    (NOT ISNULL(p.id)) ispoll, f.title ftitle".($log?', (NOT (r.time<t.lastdate OR isnull(r.time)) OR t.lastdate<fr.time) isread':'').' '
                        ."FROM threads t "
                        ."LEFT JOIN users u1 ON u1.id=t.user "
                        ."LEFT JOIN users u2 ON u2.id=t.lastuser "
                        ."LEFT JOIN polls p ON p.id=t.id "
                        ."LEFT JOIN forums f ON f.id=t.forum "
                        ."LEFT JOIN categories c ON f.cat=c.id "
                  .($log?"LEFT JOIN threadsread r ON (r.tid=t.id AND r.uid=$loguser[id]) "
                        ."LEFT JOIN forumsread fr ON (fr.fid=f.id AND fr.uid=$loguser[id]) ":'')
                        ."WHERE t.lastdate>$mintime "
                        ."  AND f.id IN ".forums_with_view_perm()." "
      ."ORDER BY t.lastdate DESC "
                        ."LIMIT ".(($page-1)*$loguser[tpp]).",".$loguser[tpp]);
    $forum[threads]=$sql->resultq("SELECT count(*) "
                                 ."FROM threads t "
                                 ."LEFT JOIN forums f ON f.id=t.forum "
                                 ."LEFT JOIN categories c ON f.cat=c.id "
                                 ."WHERE t.lastdate>$mintime "
                                 .  "AND f.id IN ".forums_with_view_perm()." ");

    function timelink($timev){
      global $time;
      if ($time == $timev) return " ".timeunits2($timev)." ";
      else return " <a href=forum.php?time=$timev>".timeunits2($timev).'</a> ';
    }

    $topbot=
        "$L[TBL] width=100%>
".      "  $L[TDn]><a href=./>Main</a> - Latest posts</td>
".      "$L[TBLend]
";
  }elseif(isset($_GET[fav]) && has_perm('view-favorites')){

    pageheader("Favorite Threads");


    $threads=$sql->query("SELECT ".userfields('u1','u1').",".userfields('u2','u2').", t.*, f.id fid, f.title ftitle, 
    (SELECT COUNT(*) FROM threadthumbs WHERE tid=t.id) AS thumbcount,


    (NOT ISNULL(p.id)) ispoll".($log?", (NOT (r.time<t.lastdate OR isnull(r.time)) OR t.lastdate<fr.time) isread":'').' '
                        ."FROM threads t "
                        ."LEFT JOIN users u1 ON u1.id=t.user "
                        ."LEFT JOIN users u2 ON u2.id=t.lastuser "
                        ."LEFT JOIN polls p ON p.id=t.id "
                        ."LEFT JOIN forums f ON f.id=t.forum "
                        ."LEFT JOIN threadthumbs th ON th.tid=t.id "
                  .($log?"LEFT JOIN threadsread r ON (r.tid=t.id AND r.uid=$loguser[id]) "
            ."LEFT JOIN forumsread fr ON (fr.fid=f.id AND fr.uid=$loguser[id]) ":'')
                        ."LEFT JOIN categories c ON f.cat=c.id "
                        ."WHERE th.uid=$loguser[id] "
                        .  "AND f.id IN ".forums_with_view_perm()." "
                        ."ORDER BY t.sticky DESC, t.lastdate DESC "
                        ."LIMIT ".(($page-1)*$loguser[tpp]).",".$loguser[tpp]);

    $forum[threads]=$sql->resultq("SELECT count(*) "
                                 ."FROM threads t "
                                 ."LEFT JOIN forums f ON f.id=t.forum "
                                 ."LEFT JOIN categories c ON f.cat=c.id "
                                 ."LEFT JOIN threadthumbs th ON th.tid=t.id "
                                 ."WHERE th.uid=$loguser[id] "
                                 .  "AND f.id IN ".forums_with_view_perm()." ");
    $topbot=
        "$L[TBL] width=100%>
".      "  $L[TDn]><a href=./>Main</a> - Favorite Threads</td>
".      "$L[TBLend]
";
  }else
  {
    error("Error", "Forum does not exist.<br> <a href=./>Back to main</a>");
  }

  $showforum=$uid||$time;

  //Forum Jump - SquidEmpress
  if(!$uid && !$time && !(isset($_GET['fav']))) {
  $r=$sql->query("SELECT c.title ctitle,c.private cprivate,f.id,f.title,f.cat,f.private FROM forums f LEFT JOIN categories c ON c.id=f.cat ORDER BY c.ord,c.id,f.ord,f.id");
		$forumjumplinks="<table><td>$fonttag Forum jump: </td>
        <td><form><select onchange=\"document.location=this.options[this.selectedIndex].value;\">";
		$c = -1;
		while($d=$sql->fetch($r))
		{
			if (!can_view_forum($d)) continue;
			
			if ($d['cat'] != $c)
			{
				if ($c != -1) $forumjumplinks .= '</optgroup>';
				$c = $d['cat'];
                $forumjumplinks.= "<optgroup label=\"".$d['ctitle']."\">";
			}
            //Based off of the forum name code in 1.92.08. - SquidEmpress
            $forumjumplinks.="<option value=forum.php?id=".$d['id'].($forum['id']==$d['id']?" selected":'').">".$d['title'];
		}
		$forumjumplinks.="</optgroup></select></table></form>";
		$forumjumplinks=($forumjumplinks);
  }

  if($forum['threads']<=$loguser['tpp']){
    $fpagelist='<br>';
    $fpagebr='';
  }else{
    $fpagelist='<div style="margin-left: 3px; margin-top: 3px; margin-bottom: 3px; display:inline-block">Pages:';
    for($p=1;$p<=1+floor(($forum['threads']-1)/$loguser['tpp']);$p++)
      if($p==$page)
        $fpagelist.=" $p";
      elseif($fid)
        $fpagelist.=" <a href=forum.php?id=$fid&page=$p>$p</a>";
      elseif($uid)
        $fpagelist.=" <a href=forum.php?user=$uid&page=$p>$p</a>";
      elseif($time)
        $fpagelist.=" <a href=forum.php?time=$time&page=$p>$p</a>";
    $fpagelist.='</div>';
    $fpagebr='<br>';
  }

  print $topbot;
  if($time) {
    print "<div style=\"margin-left: 3px; margin-top: 3px; margin-bottom: 3px; display:inline-block\">
          By Threads | <a href=thread.php?time=$time>By Posts</a></div><br>"; 
    print '<div style="margin-left: 3px; margin-top: 3px; margin-bottom: 3px; display:inline-block">'.
         timelink(900).'|'.timelink(3600).'|'.timelink(86400).'|'.timelink(604800)
   ."</div>";

 }
  print "<br>
".      "$L[TBL1]>";

if ($fid) {

echo announcement_row(0,3,4);
echo announcement_row($fid,3,4);


}

  print "
".      "  $L[TRh]>
".      "    $L[TDh] width=17>&nbsp;</td>
".      "    $L[TDh] width=17>&nbsp;</td>
".($showforum?
        "    $L[TDh]>Forum</td>":'')."
".      "    $L[TDh]>Title</td>
".      "    $L[TDh] width=130>Started by</td>
".      "    $L[TDh] width=50>Replies</td>
".      "    $L[TDh] width=50>Views</td>
".      "    $L[TDh] width=130>Last post</td>
";

  $lsticky=0;
  for($i=1;$thread=$sql->fetch($threads);$i++){
    $pagelist='';
    if($thread['replies']>=$loguser['ppp']){
      for($p=1;$p<=($pmax=(1+floor($thread['replies']/$loguser['ppp'])));$p++) {
        if($loguser['longpages'] || $p<7 || $p>($pmax-7) || !($p%10)) $pagelist.=" <a href=thread.php?id=".$thread['id']."&page=$p>$p</a>";
  else if(substr($pagelist,-1)!=".") $pagelist.=" ...";
      }
      $pagelist=" <font class=sfont>(pages: $pagelist)</font>";
    }

    $status='';
    $statalt='';
    if($thread['closed']){                $status.='o'; $statalt='OFF'; }
    if($thread['replies']>=50){           $status.='!'; if(!$statalt) $statalt='HOT'; }

    if($log){
      if(!$thread['isread']){ $status.='n'; if($statalt!='HOT') $statalt='NEW'; }
    }else
      if($thread['lastdate']>(ctime()-3600)){ $status.='n'; if($statalt!='HOT') $statalt='NEW'; }

    if($status)
      $status=rendernewstatus($status);
    else
      $status='&nbsp;';

    if(!$thread['title'])
      $thread['title']='?';

    if($thread['icon'])
      $icon="<img src='$thread[icon]' style='max-height: 17px; max-width: 17px;'>";
    else
      $icon='&nbsp;';

    if($thread['sticky'])
      $tr='TR1c';
    else
      $tr=($i%2?'TR2':'TR3').'c';

    if(!$thread['sticky'] && $lsticky)
      print
          "  ".$L['TRg'].">
".        "    ".$L['TD']." colspan=".($showforum?8:7)." style='font-size:1px'>&nbsp;</td>
";
    $lsticky=$thread['sticky'];

    $taglist="";
    for($k=0;$k<sizeof($tags);++$k) {
      $t=$tags[$k];
      if($thread['tags'] & (1<<$t['bit'])) {
        if ($config['classictags']) {
	  list($r,$g,$b) = sscanf($t[color],"%02X%02X%02X"); //updated to new php syntax, call by reference is now completely removed in PHP
          if($r<128 && $g<128) { $r+=32; $g+=32; }
          $t[color2]=sprintf("%02X%02X%02X",$r,$g,$b);
          $taglist.=" <span style=\"background-repeat:repeat;background:url('gfx/tpng.php?c=$t[color]&t=105');font-size:7pt;font-family:Small Fonts,sans-serif;padding:1px 1px\">"
                ."<span style=\"background-repeat:repeat;background:url('gfx/tpng.php?c=$t[color]&t=105');font-size:7pt;font-family:Small Fonts,sans-serif;color:$t[color2];padding:2px 3px\" alt=\"$t[name]\">$t[tag]</span></span>";
        }
        
        else $taglist.=" <img src=\"./gfx/tags/tag$t[fid]-$t[bit].png\" alt=\"$t[name]\" title=\"$t[name]\" style=\"position: relative; top: 3px;\"/>";
      }
    }

    print "  $L[$tr]>
".        "    $L[TD1]>$status</td>
".        "    $L[TD]>$icon</td>
".($showforum?
          "    $L[TD]><a href=forum.php?id=$thread[fid]>$thread[ftitle]</a></td>":'')."
".        "    $L[TDl] style=\"word-break: break-all;\">".($thread['ispoll']?"<img src=img/poll.gif height=10>":"").(($thread['thumbcount'])?" (".$thread['thumbcount'].") ":"")."<a href=\"thread.php?id=".$thread['id']."\">".htmlval($thread['title'])."</a>$taglist$pagelist</td>
".        "    $L[TD]>".userlink($thread,'u1',$config['startedbyminipic'])."</td>
".        "    $L[TD]>".$thread['replies']."</td>
".        "    $L[TD]>".$thread['views']."</td>
".        "    $L[TD]><nobr>".cdate($dateformat,$thread['lastdate'])."</nobr><br><font class=sfont>by&nbsp;".userlink($thread,'u2',$config['forumminipic'])."&nbsp;<a href=\"thread.php?pid=".$thread['lastid']."#".$thread['lastid']."\">&raquo;</a></font></td>
";
  }
  print "$L[TBLend]
".      "$forumjumplinks$fpagelist$fpagebr
".      "$topbot
";
  pagefooter();
?>
