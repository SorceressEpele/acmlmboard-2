<?php
  require 'lib/common.php';
  require 'lib/threadpost.php';
  loadsmilies();

    $announce=checkvar('_REQUEST','announce');
    checknumeric($announce);

	//--
	if ($act = checkvar('_POST','action')) {
		check_token($_POST['auth']);
		$fid = (int) $_POST['fid'];
	} else {
		$fid = (int) $_GET['id'];
	}
	$user   = $loguser;
	$userid = $loguser['id'];
	//--

  checknumeric($fid);

  needs_login(1);

  if ($announce) {
    $type = "announcement";
    $typecap = "Announcement";
  }
  elseif (checkvar('_GET','ispoll')) {
    $type = "poll";
    $typecap = "Poll";
	$ispoll = 1;
  }
  else {
    $type = "thread";
    $typecap = "Thread";
	$ispoll = 0;
  }


  if ($announce && $fid==0)
	$forum = array('id' => 0, 'readonly' => 1);
  else
    $forum=$sql->fetchq("SELECT * FROM forums WHERE id=$fid AND id IN ".forums_with_view_perm());
	
if($act!="Submit"){
  $extjs="<script language=\"javascript\" type=\"text/javascript\" src=\"tools.js\"></script>";
  $toolbar= posttoolbar($loguser['posttoolbar']);
	 
	if (checkvar('ispoll'))
	{
		$extjs.='<script type="text/javascript" src="jscolor/jscolor.js"></script><script type="text/javascript" src="polleditor.js"></script>';
		$optfield = '<div><input type="text" name="opt[]" size=40 maxlength=40 value="%s"> - Color: <input class="color" name="col[]" value="%02X%02X%02X"> - <button class="submit" onclick="removeOption(this.parentNode);return false;">Remove</button></div>';
	}
}
  $tagsin="";
  $t=$sql->query("SELECT * FROM tags WHERE fid=$fid");
  while($tt=$sql->fetch($t)) {
    if($tagsin=="") $tagsin=
          "$L[TR]>
".        "  $L[TD1c]>$typecap tags:</td>
".        "  $L[TD2]>
";
    $tagsin.="<input type='checkbox' name='tag$tt[bit]' id='tag$tt[bit]' value='1' ".(checkvar('_POST',"tag$tt[bit]")?"checked":"")."><label for='tag$tt[bit]'>$tt[name]</label> ";
  }
  if($tagsin!="") $tagsin.="</tr>";

  $forumlink="<a href=forum.php?id=$fid>Back to forum</a>";

  if(!$forum) {
	error("Error", "Forum does not exist. <br> <a href=./>Back to main</a>");
  }

    else if ($announce && !can_create_forum_announcements($fid))
    $err = "    You have no permissions to create announcements in this forum!<br>$forumLink";

     else if (!can_create_forum_thread($forum)){

  $err="    You have no permissions to create threads in this forum!<br>$forumlink";
  }

  else if($user['lastpost']>ctime()-30 && $act=='Submit' && !has_perm('ignore-thread-time-limit'))
      $err="    Don't post threads so fast, wait a little longer.<br>
".         "    $forumlink";

  //2007-02-19 //blackhole89 - table breach protection
  if($act=='Submit'){
    $title = $_POST['title'];
    $message =  $_POST['message'];
    if(strlen($message)>60000)  // Protection against huge posts getting cut off
      $err="    This post is too long. Maximum length: 60000 characters. <br>
".         "    $threadlink";
    if(($tdepth=tvalidate($message))!=0)
      $err="    This post would disrupt the board's table layout! The calculated table depth is $tdepth.<br>
".         "    $forumlink";
    $invalidchars=array(">","\"","'");
    if(strlen(trim(str_replace($invalidchars,"",$_POST['iconurl'])))<strlen($_POST['iconurl']))
      $err="    Invalid thread icon.<br>
".         "    $backlink";
    if(strlen(trim(str_replace(" ","",$title)))<4)
      $err="    You need to enter a longer $type title.<br>
".         "    $forumlink";
    if($ispoll && (!isset($_POST['opt']) || count($_POST['opt']) < 2))
      $err="    You must add atleast two choices to your poll.<br>
".         "    $forumlink";
    else if($ispoll) {
      foreach ($_POST['opt'] as $id => $text)
        if(trim($text) == '' || $_POST['col'][$id] == '')
          $err="You must fill in all poll choices' fields.<br>
".             "$forumlink";
    }
  }

  $top="<a href=./>Main</a> - <a href=forum.php?id=$fid>$forum[title]</a> - New $type";

  $i=1;
  $icons=$sql->query('SELECT * FROM posticons ORDER BY id');
  $iconlist="";
  while($icon=$sql->fetch($icons))
    $iconlist.=
          "      $L[INPr]=iconid value=$i> <img src=$icon[url]>&nbsp; &nbsp;".(!($i++%10)?'<br>':'')."
";
  $iconlist.=
          "      $L[INPr]=iconid value=0 checked> None&nbsp; &nbsp;
".        "      Custom: $L[INPt]=iconurl size=40 maxlength=100>
";

  if(isset($err)){
    pageheader("New $type",$forum['id']);
    print "$top - Error";
    noticemsg("Error", $err);
  }elseif(!$act){
    if(checkvar('ispoll')){
      $pollin=
          "$L[TR]>
".        "  $L[TD1c]>Poll question:</td>
".        "  $L[TD2]>$L[INPt]=question size=100 maxlength=100 value=\"".htmlval(checkvar('_POST','question'))."\"></td>
".        "$L[TR]>
".        "  $L[TD1c]>Poll choices:</td>
".        "  $L[TD2]><div id=\"polloptions\">
".        "    ".sprintf($optfield, '', rand(0,255), rand(0,255), rand(0,255))."
".        "    ".sprintf($optfield, '', rand(0,255), rand(0,255), rand(0,255))."
".        "  </div>
".        "  $L[BTTn]=addopt onclick=\"addOption();return false;\">Add choice</button></td>
".        "$L[TR]>
".             "  $L[TD1c]>Options:</td>
".             "  $L[TD2]>$L[INPc]=multivote value=1 id=mv><label for=mv>Allow multiple voting</label> | $L[INPc]=changeable checked value=1 id=ch><label for=ch>Allow changing one's vote</label>
";
    }
 pageheader("New $type",$forum['id']);
    print "$extjs $top
".        "<br><br>
".        "<form action=newthread.php?ispoll=$ispoll method=post>
".        " $L[TBL1]>
".        "  $L[TRh]>
".        "    $L[TDh] colspan=2>$typecap</td>
".        "  $L[TR]>
".        "    $L[TD1c]>$typecap title:</td>
".        "    $L[TD2]>$L[INPt]=title size=100 maxlength=100></td>
".        "  $L[TR]>
".        "    $L[TD1c]>$typecap icon:</td>
".        "    $L[TD2]>
".        "$iconlist
".        "    </td>
".$tagsin."
".checkvar('pollin')."
";
     if($loguser['posttoolbar']!=1)
print     "  $L[TR]>
".        "    $L[TD1c] width=120>Format:</td>
".        "    $L[TD2]>$L[TBL]>$L[TR]>$toolbar$L[TBLend]
";
print     "  $L[TR]>
".        "    $L[TD1c] width=120>Post:</td>
".        "    $L[TD2]>$L[TXTa]=message id='message' rows=20 cols=80></textarea></td>
".        "  $L[TR1]>
".        "    $L[TD]>&nbsp;</td>
".        "    $L[TD]>
".        "      ".auth_tag()."
".        "      $L[INPh]=fid value=$fid>
".        "      $L[INPh]=announce value=$announce>
".        "      $L[INPs]=action value=Submit>
".        "      $L[INPs]=action value=Preview>
".        // 2009-07 Sukasa: Newthread mood selector, just in the place I put it in mine
          "      $L[INPl]=mid>".moodlist()."
".        "      $L[INPc]=nolayout id=nolayout value=1 ".(checkvar('_POST','nolayout')?"checked":"")."><label for=nolayout>Disable post layout</label>
".        "      $L[INPc]=nosmile id=nosmile value=1 ".(checkvar('_POST','nosmile')?"checked":"")."><label for=nosmile>Disable smilies</label>
".        "      $L[INPc]=filter id=filter value=1 ".(checkvar('_POST','filter')?"checked":"")."><label for=filter>Flag as NSFW</label>
";
    if(can_edit_forum_threads($fid) && !$announce)
    print "     $L[INPc]=close id=close value=1 ".(checkvar('_POST','close')?"checked":"")."><label for=close>Close thread</label>
".        "      $L[INPc]=stick id=stick value=1 ".(checkvar('_POST','stick')?"checked":"")."><label for=stick>Stick thread</label>
";
    print "    </td>
".        " $L[TBLend]
".        "</form>
";
  }elseif($act=='Preview'){
    $_POST['title']  =stripslashes($_POST['title']);
    $_POST['message']=stripslashes($_POST['message']);

    $post['date']=ctime();
    $post['ip']=$userip;
    $post['num']=++$user['posts'];
    $post['text']=$_POST['message'];
    $post['mood'] = (isset($_POST['mid']) ? (int)$_POST['mid'] : -1); // 2009-07 Sukasa: Newthread preview
    $post['nolayout']=checkvar('_POST','nolayout');
    $post['nosmile']=checkvar('_POST','nosmile');
    $post['close']=checkvar('_POST','close');
    $post['stick']=checkvar('_POST','stick');
    foreach($user as $field => $val)
      $post['u'.$field]=$val;
    $post['ulastpost']=ctime();

    if($ispoll){
      $_POST['question']=stripslashes($_POST['question']);
      $pollprev="<br>$L[TBL1]>
".        "  $L[TR1]>
".        "    $L[TD1] colspan=2>".htmlval($_POST['question'])."
";
      $pollin=
          "$L[TR]>
".        "  $L[TD1c]>Poll question:</td>
".        "  $L[TD2]>$L[INPt]=question size=100 maxlength=100 value=\"".htmlval($_POST['question'])."\"></td>
".        "$L[TR]>
".        "  $L[TD1c]>Poll choices:</td>
".        "  $L[TD2]><div id=\"polloptions\">
";

      if (isset($_POST['opt']))
	  {
		  foreach ($_POST['opt'] as $id => $text)
		  {
			$text = htmlval(stripslashes($text));
			
			$color = stripslashes($_POST['col'][$id]);
			list($r,$g,$b) = sscanf(strtolower($color), '%02x%02x%02x');
			
			$pollin .= "    ".sprintf($optfield, $text, $r, $g, $b)."\n";
			$pollprev .= "$L[TR2]>$L[TD2]>{$text} $L[TD3]><img src=\"gfx/bargraph.php?z=1&n=1&r={$r}&g={$g}&b={$b}\">";

		  }
	  }
	  
      $pollin.="  </div>
".             "  $L[BTTn]=addopt onclick=\"addOption();return false;\">Add choice</button></td>
".             "$L[TR]>
".             "  $L[TD1c]>Options:</td>
".             "  $L[TD2]>$L[INPc]=multivote ".(checkvar('_POST','multivote')?"checked":"")." value=1 id=mv><label for=mv>Allow multiple voting</label> | $L[INPc]=changeable ".(checkvar('_POST','changeable')?"checked":"")." value=1 id=ch><label for=ch>Allow changing one's vote</label>
";
$pollprev.="$L[TBLend]";
    }
$post['id'] = $post['deleted'] = $post['revision'] = $post['maxrevision'] = $post['thread'] = 0;
$post['user'] = $loguser['id'];
 pageheader("New $type",$forum['id']);
    print "$extjs $top - Preview
".        checkvar('pollprev')."<br>
".        "$L[TBL1]>
".        "  $L[TRh]>
".        "    $L[TDh] colspan=2>Post preview
".        "$L[TBLend]
".         threadpost($post,0)."
".        "<br>
".        "<form action=newthread.php?ispoll=$ispoll method=post>
".        " $L[TBL1]>
".        "  $L[TRh]>
".        "    $L[TDh] colspan=2>$typecap</td>
".        "  $L[TR]>
".        "    $L[TD1c]>$typecap title:</td>
".        "    $L[TD2]>$L[INPt]=title size=100 maxlength=100 value=\"".htmlval($_POST['title'])."\"></td>
".$tagsin."
".checkvar('pollin')."
";
     if($loguser['posttoolbar']!=1)
print     "  $L[TR]>
".        "    $L[TD1c] width=120>Format:</td>
".        "    $L[TD2]>$L[TBL]>$L[TR]>$toolbar$L[TBLend]
";
print     "  $L[TR]>
".        "    $L[TD1c] width=120>Post:</td>
".        "    $L[TD2]>$L[TXTa]=message id='message' rows=10 cols=80>".htmlval($_POST['message'])."</textarea></td>
".        "  $L[TR1]>
".        "    $L[TD]>&nbsp;</td>
".        "    $L[TD]>
".        "      $L[INPh]=auth value=\"".htmlval($_POST['auth'])."\">
"./*      "      $L[INPh]=passenc value=\"$pass\">
".*/
          "      $L[INPh]=fid value=$fid>
".        "      $L[INPh]=iconid value=$_POST[iconid]>
".        "      $L[INPh]=iconurl value=$_POST[iconurl]>
".        "      $L[INPh]=announce value=$announce>
".        "      $L[INPs]=action value=Submit>
".        "      $L[INPs]=action value=Preview>
".        // 2009-07 Sukasa: Newthread mood selector, just in the place I put it in mine
          "      $L[INPl]=mid>".moodlist($_POST['mid'])."
".        "      $L[INPc]=nolayout id=nolayout value=1 ".($post['nolayout']?"checked":"")."><label for=nolayout>Disable post layout</label>
".        "      $L[INPc]=nosmile id=nosmile value=1 ".($post['nosmile']?"checked":"")."><label for=nosmile>Disable smilies</label>
".        "      $L[INPc]=filter id=filter value=1 ".(checkvar('_POST','filter')?"checked":"")."><label for=filter>Flag as NSFW</label>
";
    if(can_edit_forum_threads($fid) && !$announce)
    print "     $L[INPc]=close id=close value=1 ".($post['close']?"checked":"")."><label for=close>Close thread</label>
".        "      $L[INPc]=stick id=stick value=1 ".($post['stick']?"checked":"")."><label for=stick>Stick thread</label>
";
    print "    </td>
".        " $L[TBLend]
".        "</form>
";
  }elseif($act=='Submit'){
    if(!($iconurl=$_POST['iconurl']))
      $iconurl=$sql->resultq("SELECT url FROM posticons WHERE id=".(int)$_POST['iconid']);
    $nolayout=checkvar('_POST','nolayout');
    $nosmile=checkvar('_POST','nosmile');
    $filter=checkvar('_POST','filter');
    checknumeric($nolayout);
    checknumeric($nosmile);
    checknumeric($filter);
    if(can_edit_forum_threads($fid)){
    	checknumeric($_POST['close']);
    	checknumeric($_POST['stick']);
        if(checkvar('_POST','close')) $modclose="1";
        if(checkvar('_POST','stick')) $modstick="1";
    }

    if(!$_POST['close']) $modclose="0";
    if(!$_POST['stick']) $modstick="0";

    $iconurl=addslashes($iconurl);

    $user=$sql->fetchq("SELECT * FROM users WHERE id=$userid");
    $user['posts']++;

    $tagsum=0;
    for($i=0;$i<32;++$i) if(isset($_POST["tag$i"])) $tagsum|=(1<<$i);
    
    $mid=(isset($_POST['mid']) ? (int)$_POST['mid'] : -1);
    if($announce) {
    $modclose=$announce;
    }

    $sql->query("UPDATE users SET posts=posts+1,threads=threads+1,lastpost=".ctime()." "
               ."WHERE id=$userid");
    $sql->query("INSERT INTO threads (title,forum,user,lastdate,lastuser,icon,tags,announce,closed,sticky,filter) "
               ."VALUES ('".addslashes($_POST[title])."',$fid,$userid,".ctime().",$userid,'$iconurl',$tagsum,$announce,$modclose,$modstick,$filter)");
    $tid=$sql->insertid();
    $sql->query("INSERT INTO posts (user,thread,date,ip,num,mood,nolayout,announce,nosmile) "
               ."VALUES ($userid,$tid,".ctime().",'$userip',$user[posts],$mid,$nolayout,$announce,$nosmile)");
    $pid=$sql->insertid();
    $sql->query("INSERT INTO poststext (id,text) VALUES ($pid,'$message')");
if (!$announce)   {
   $sql->query("UPDATE forums SET threads=threads+1,posts=posts+1,lastdate=".ctime().",lastuser=$userid,lastid=$pid "
               ."WHERE id=$fid");
}
    $sql->query("UPDATE threads SET lastid=$pid WHERE id=$tid");

    if($ispoll)
    {
//Quick sanity check on these, as PHP is discarding unticked boxes upon submitting.
      if(!isset($_POST['multivote']))  $_POST['multivote']=false;
      if(!isset($_POST['changeable'])) $_POST['changeable']=false;
      $sql->query("INSERT INTO polls (id,question,multivote,changeable) VALUES ($tid,'{$_POST['question']}','{$_POST['multivote']}','{$_POST['changeable']}')");
	  
      foreach ($_POST['opt'] as $id => $_text)
	  {
	    $color = stripslashes($_POST['col'][$id]);
		list($r,$g,$b) = sscanf(strtolower($color), '%02x%02x%02x');
		$text = $sql->escape($_text);
		
        $sql->query("INSERT INTO polloptions (`poll`,`option`,r,g,b) VALUES ($tid,'{$text}',".(int)$r.",".(int)$g.",".(int)$b.")");
	  }
    }

    // bonus shit
    if ($announce) {
        // No coin bonus for announcements
        $viewlink  = "thread.php?announce=".$forum['id'];
        $shortlink = "a=".$forum['id'];
        $message   = "The ".($forum['id'] ? "forum " : "")."announcement has been posted.";
    } else {
        $c = rand(250,750);
        $sql->query("UPDATE `usersrpg` SET `spent` = `spent` - '$c' WHERE `id` = '$userid'");
		
		$viewlink  = "thread.php?id=$tid";
        $shortlink = "t=$tid";
        $message   = ucfirst($type)." posted successfully! (Gained $c bonus coins)";
    }
	
    $chan = $sql->resultp("SELECT chan FROM announcechans WHERE id=?",array($forum['announcechan_id']));
	
if ($announce && $forum['id']==0) {
     sendirc("{irccolor-base}New $type by {irccolor-name}".get_irc_displayname()."{irccolor-url}: {irccolor-name}".stripslashes($_POST[title])."{irccolor-base} - {irccolor-url}{boardurl}?$shortlink{irccolor-base}",$chan);
}
else if ($announce) {
     sendirc("{irccolor-base}New forum $type by {irccolor-name}".get_irc_displayname()."{irccolor-base} in {irccolor-title}$forum[title]{irccolor-url}: {irccolor-name}".stripslashes($_POST[title])."{irccolor-base} - {irccolor-url}{boardurl}?$shortlink{irccolor-base}",$chan);  
}
else {
     
     sendirc("{irccolor-base}New $type by {irccolor-name}".get_irc_displayname()."{irccolor-base} in {irccolor-title}".$forum['title']."{irccolor-url}: {irccolor-name}".stripslashes($_POST['title'])."{irccolor-base} - {irccolor-url}"."{boardurl}?$shortlink{irccolor-base}",$chan);

}
    redirect($viewlink, $message, "Posted", "the {$type}");
  }

  pagefooter();
?>
