<?php


  function checkuser($name,$pass){
    global $sql;
    $id=$sql->resultq("SELECT id FROM users WHERE (name='$name' OR displayname='$name') AND pass='$pass'");
    if(!$id) $id=0;
    return $id;
  }

  function forumbanned($uid,$fid) { //2009/07 Sukasa: Forum Bans
    global $sql;
    checknumeric($uid);
    checknumeric($fid);
    return $sql->resultq("select count(`uid`) > 0 from `forumbans` where `forum`=$fid and `uid`=$uid");
  }

  function checkuid($userid,$pass){
    global $sql;
    checknumeric($userid);
    $id=$sql->resultq("SELECT id FROM users WHERE id=$userid AND pass='".addslashes($pass)."'");
    if(!$id) $id=0;
    return $id;
  }

  function checkctitle(){
    global $loguser;
    if(!$loguser[id]) return false;
    if (has_perm_revoked('edit-title')) return false;
    if (has_perm('edit-title')) return true;
    if($loguser[posts]>=100) return true; //1200
    if($loguser[posts]>50 && $loguser[regdate]<(time()-3600*24*60)) return true; //800, 200
    
    return false;
  }

function renderdotrank($posts=0){
      //This function takes the number of posts a user has ($posts), and returns the html to be printed out. 
     
      $postcount = $posts; // Save the actual post count before we get the mod value

      // Protection again overflow of dots. This normally won't be triggered in a real-world situation.
      if($posts > 10000)
      {
        $posts %= 100010;
        if($posts < 10)
          $posts = 10;
      }

      $ranknum=floor($posts / 10) * 10;
      //Based off of AB 1.x code. Well.. more or is the same code...
        if ($postcount > 0)
        {
          $pr[5] = 5000;
          $pr[4] = 1000;
          $pr[3] =  250;
          $pr[2] =   50;
          $pr[1] =   10;

          $postsx = $posts;
          $dotnum[5] = floor($postsx / $pr[5]);
          $postsx = $postsx - $dotnum[5] * $pr[5];
          $dotnum[4] = floor($postsx / $pr[4]);
          $postsx = $postsx - $dotnum[4] * $pr[4];
          $dotnum[3] = floor($postsx / $pr[3]);
          $postsx = $postsx - $dotnum[3] * $pr[3];
          $dotnum[2] = floor($postsx / $pr[2]);
          $postsx = $postsx - $dotnum[2] * $pr[2];
          $dotnum[1] = floor($postsx / $pr[1]);

          $rank = "<span title=\"$ranknum\"> ";

          foreach($dotnum as $dot => $num) 
          {
            for ($x = 0; $x < $num; $x++) 
            {
              $rank .= "<img src=img/dots/dot". $dot .".gif align=\"absmiddle\">";
            }
          }

          if ($postcount < 10) return "Newbie";
          //else $rank .= "</span><br>$ranknum"; //This will show number the original way.
          else $rank .= "</span>";

        }
        else return "Non-Poster";

      return $rank;

    }

  function getrank($set,$posts){
    global $ranks,$sql;
    if($set == "-1") return renderdotrank($posts);

    //[KAWA] Climbing the Ranks Again
    if($posts > 5100)
    {
      $posts %= 5000;
      if($posts < 10)
        $posts = 10;
    }

    if($set) {
      $d=$sql->fetchq("SELECT str FROM ranks WHERE rs=$set AND p<=$posts ORDER BY p DESC LIMIT 1");
      return $d[0];
    }
    return "";
  }

  function randnickcolor(){
    /* OLD HACKISH CODE FOR APRIL 5 */
    $stime=gettimeofday();
    $h=(($stime[usec]/5)%600);
    if($h<100){
      $r=255;
      $g=155+$h;
      $b=155;
    }elseif($h<200){
      $r=255-$h+100;
      $g=255;
      $b=155;
    }elseif($h<300){
      $r=155;
      $g=255;
      $b=155+$h-200;
    }elseif($h<400){
      $r=155;
      $g=255-$h+300;
      $b=255;
    }elseif($h<500){
      $r=155+$h-400;
      $g=155;
      $b=255;
    }else{
      $r=255;
      $g=155;
      $b=255-$h+500;
    }
    $rndcolor=substr(dechex($r*65536+$g*256+$b),-6);
    $namecolor="color=$rndcolor";
    return $rndcolor;   
  }

 function userlink_by_id($uid,$usemini='') {
    global $sql;
    $u = $sql->fetchp("SELECT id,name,displayname,power,minipic FROM users WHERE id=?",array($uid));        
    $u['showminipic']=$usemini;   
    return userlink($u);
 }

 function userlink($user,$u='',$usemini=''){
    global $loguser;
    if(!$user[$u.name])
      $user[$u.name]='&nbsp;';

    return '<a href=profile.php?id='.$user[$u.id].'>'
          .userdisp($user,$u,$usemini)
          .'</a>';
  }

  function userdisp($user,$u='',$usemini=''){
    global $sql;
    if($user[$u.'power']<0)
      $user[$u.'power']='x';
    //$usemini = true;
    if($usemini) $user['showminipic'] = true;


  //global $loguser;
 // if ($loguser['id'] != 640 && $user[$u.name] == "smwedit") $user[$u.name] = "smwdork"; 
/* Broken as of 2011-09-18 -Emuz */
  $nc= color_for_user($user[$u.'id']);
  if($nc=="RAINBOW") $nc=randnickcolor();
  $n = $user[$u.'name'];
  if($user[$u.'displayname'])
  	$n = $user[$u.'displayname'];
   if($user[$u.'minipic'] && $user['showminipic']) $minipic="<img style='vertical-align:text-bottom' src='".$user[$u.'minipic']."' border=0> ";
   else $minipic="";
  return "$minipic<font color='#$nc'>" //class=nc".$user[$u.sex].$user[$u.power].'>'
  //return "$minipic<font class=nc".$user[$u.sex].$user[$u.power].'>'
         .str_replace(" ","&nbsp;",htmlval($n))
         .'</font>';
 /* return '<font color=#'. $c .'>'
          .htmlval($user[$u.name])
          .'</font>';

    return '<font '. $namecolor .'>'
          .htmlval($user[$u.name])
          .'</font>';
*/
}


?>