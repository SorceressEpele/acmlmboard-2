<?php
  require 'lib/common.php';
  needs_login(1);
// Support for larger-than-spec avatars
  if($avatardimx>=180){ $avax=$avatardimx; } else { $avax=180; }
  if($avatardimy>=180){ $avay=$avatardimy; } else { $avay=180; }
//Permissions
  if(isset($_GET['user'])){
    $edid=$_GET['user'];
    $lnkex="?user=$edid";
  } else {
    $edid=$loguser['id'];
    $lnkex="";
  }
  $edid = (int)$edid;
  if(!can_edit_user_moods($edid)){
    error("Error", "You have no permissions to do this!<br> <a href=./>Back to main</a>");
  }
  
//Editing functionality
  if(isset($_POST['id']) && $_POST['a']=="Save"){
    if(is_numeric($_POST['id'])){
      $usrmoodz=@$sql->result($sql->query("SELECT count(*) FROM `mood` WHERE `user`=".$edid." AND `id`!=".$_POST['id']),0,0); //Collect count of user moods.
      $fname=$_FILES['picture'];
      if($fname['size']>0){
        if($_POST['id']!=-1){
          if($usrmoodz<$avatarmoods){
            $ava_out=img_upload($fname,"userpic/".$edid."_".$_POST['id'],$avatardimx,$avatardimy,$avatarsize);
          } else {
            $err.="Too many mood avatars.";
	  }
        } else {//Default Avatar
          $sql->query("UPDATE `users` SET `usepic`=`usepic`+1 WHERE `id`=".$edid);
          $ava_out=img_upload($fname,"userpic/".$edid,$avatardimx,$avatardimy,$avatarsize);
        }
        if($ava_out=="OK!"){
          if($_POST['id']!=-1){ $sql->query("REPLACE INTO `mood` VALUES (".$_POST['id'].",".$edid.",'".addslashes($_POST['label'])."',1,'')"); }
        } else { $err.=$ava_out; }
      } else { //No file uploaded
        if(strlen($_POST['url'])>0) {
          $img_data=getimagesize($_POST['url']); 
          $ftypes=array("png","jpeg","jpg","gif");
          if($config['avatarwebp']==true) $ftypes[]="webp";
          if($img_data[0]>$avatardimx){ $err="Image linked is too wide.<br>"; }
          if($img_data[1]>$avatardimy){ $err=checkvar('err')."Image linked is too tall.<br>"; }
          if(!in_array(str_replace("image/","",$img_data['mime']),$ftypes)){ $err=checkvar('err')."Image linked is not a gif, jpg or png file.<br>Image is detected as <i>".$img_data['mime']."</i> type.";}
          if(!isset($err)){ $sql->query("REPLACE INTO `mood` VALUES (".$_POST['id'].",".$edid.",'".addslashes($_POST['label'])."',0,'".addslashes($_POST['url'])."')"); }
        } else {//No url specified.
          $sql->query("UPDATE `mood` SET `label`='".addslashes($_POST['label'])."' WHERE `id`=".$_POST['id']." AND `user`=".$edid);
        }
      }
    } else { $err="Bad id"; }
  if(!isset($err)){ $err="Mood avatar updated."; }
  }
  if(isset($_POST['id']) && $_POST['a']=="Delete"){
    if(is_numeric($_POST['id'])){
      if($_POST['id']==-1){
        $sql->query("UPDATE `users` SET `usepic`=0 WHERE `id`=".$edid);
        echo "Default avatar set to blank.";
      } else {
        //Delete mood avatar
	if(unlink("userpic/".$edid."_".$_POST['id'])){
          $sql->query("DELETE FROM `mood` WHERE `id`=".$_POST['id']." AND `user`=".$edid);
          //Update posts which use this deleted avatar to use the user's default instead.
          $sql->query("UPDATE `posts` SET `mood`=-1 WHERE `mood`=".$_POST['id']." AND `user`=".$edid);
          echo "Deleted.";
        } else {
          echo "Error deleting avatar.";
        }
      }
    } else {
      echo "Bad id.";
    }
    die(); //Don't render page.
  }
  pageheader('Mood Avatar Editor');
//Various magic
  if(isset($err)){
    noticemsg("Notice", $err);
  }
  if(isset($lnkex)){ $ajax="&x_ajax=1"; } else { $ajax="?x_ajax=1"; }
  print "<script language=\"javascript\">
	function edit(av_id, av_lab, av_url)
	{
		document.getElementById(\"editpane\").style['display'] = \"inline\";
		document.getElementById(\"id\").value = av_id;
		document.getElementById(\"label\").value = av_lab;
		document.getElementById(\"url\").value = av_url;
		if(av_id==-1){
			document.getElementById(\"em\").style['display'] = \"none\";
			document.getElementById(\"em2\").style['display'] = \"none\";
		} else {
			document.getElementById(\"em\").style['display'] = \"\";
			document.getElementById(\"em2\").style['display'] = \"\";
		} 
	}
	function del(av_id, av_lab)
	{
		if(confirm(\"Are you sure you wish to delete \"+av_lab+\"?\")){
		y = new XMLHttpRequest();
		y.onreadystatechange = function()
		{
			if(y.readyState == 4)
			{
				if(y.responseText != \"OK\")
					alert(y.responseText);
				if(y.responseText == \"Deleted.\")
					document.getElementById(\"mood\"+av_id).style['display'] = \"none\";
				if(y.responseText == \"Default avatar set to blank.\")
					document.getElementById(\"defava\").style['background'] = \"none\";
			}
		};
		y.open('POST','mood.php$lnkex$ajax',true);
		y.setRequestHeader('Content-type','application/x-www-form-urlencoded');
		y.send('a=Delete&id='+av_id);
		}
	}
</script>";
//Default Avatar.
  $u=$sql->fetch($sql->query("SELECT `usepic` FROM `users` WHERE `id`=".$edid));
  if($u['usepic']>=1){ $aurl="gfx/userpic.php?id=".$edid."&r=".$u['usepic']; } else { $aurl=""; }
  print "<div style=\"margin: 4px; float: left; display:inline-block;\">$L[TBL1]>
  $L[TRh]>
    $L[TDh]>Default</td>
  </tr>
  $L[TR]>
    $L[TD2]><div style=\"padding: 0px; margin: 0px; width: ".$avax."px; height: ".$avay."px; background: url($aurl) no-repeat center;\" id=\"defava\"></div>
  </tr>$L[TR]>
    $L[TD1]><a href=\"#\" onclick=\"edit(-1,'','')\">Edit</a> | <a href=\"#\" onclick=\"del(-1,'Default')\">Delete</a></td>
  </tr>
</table></div>";

//Mood Avatars
  $fid=0;
  $lid=0;
  $avas = $sql->query("SELECT * FROM `mood` WHERE `user`=".$edid);
  for($i=1;$mav=$sql->fetch($avas);$i++){
  if($lid!=($mav['id']-1) && $fid==0){ $fid=($mav['id']-1); } //Find a "free" ID.
  $lid=$mav['id'];
  if($mav['local']==1){
    $aurl="gfx/userpic.php?id=".$edid."_".$mav['id'];
  } else {
    $aurl=stripslashes($mav['url']);
  }
  print "<div style=\"margin: 4px; float: left; display:inline-block;\" id=\"mood".$mav['id']."\">$L[TBL1]>
  $L[TRh]>
    $L[TDh]>".stripslashes($mav['label'])."</td>
  </tr>
  $L[TR]>
    $L[TD2]><div style=\"padding: 0px; margin: 0px; width: ".$avax."px; height: ".$avay."px; background: url(".$aurl.") no-repeat center;\"></div>
  </tr>$L[TR]>
    $L[TD1]><a href=\"#editpane\" onclick=\"edit(".$mav['id'].",'".htmlspecialchars($mav['label'])."', '".$mav['url']."')\">Edit</a> | <a href=\"#\" onclick=\"del(".$mav['id'].",'".htmlspecialchars($mav['label'])."')\">Delete</a></td>
  </tr>
</table></div>";
}
if($fid==0){ $fid=$lid+1; } //If no free ID.
if($fid<=$avatarmoods){
  print "<div style=\"margin: 4px; float: left; display:inline-block;\" id=\"mood".$avatarmoods."\">$L[TBL1]>
  $L[TRh]>
    $L[TDh] style=\"width:180px;\">&nbsp</td>
  </tr>
  </tr>$L[TR]>
    $L[TD1]><a href=\"#editpane\" onclick=\"edit(".$fid.",'(Label)', '')\">Add New</a></td>
  </tr>
</table></div>";
}
  print "<br clear=\"all\"><div id=\"editpane\" style=\"display:none;\"><form id=\"f\" action=\"mood.php$lnkex\" enctype=\"multipart/form-data\" method=\"post\">$L[TBL1]>
  $L[TRh]>
    $L[TDh] colspan=2>Editing mood avatar</td>
  </tr>
  $L[TR] id=\"em\">
    $L[TD1]>Label</td>
    $L[TD2]>$L[INPt]=\"label\" id=\"label\" size=50 maxlength=100></td>
  </tr>$L[TR]>
    $L[TD1]>Upload File</td>
    $L[TD2]><input type=\"file\" name=\"picture\" size=50></td>
  </tr>$L[TR] id=\"em2\">
    $L[TD1]>Web link</td>
    $L[TD2]>$L[INPt]=\"url\" id=\"url\" size=50 maxlength=250></td>
  </tr>$L[TR]>
    $L[TD1]><input type=\"hidden\" name=\"id\" id=\"id\"></td>
    $L[TD2]><input type=\"submit\" name='a' value=\"Save\"></td>
  </tr></table></form></div>";
  pagefooter();
?>
