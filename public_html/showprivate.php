<?php
  require 'lib/common.php';
  require 'lib/threadpost.php';

  needs_login(1);

  if (!has_perm('view-own-pms')) {
    error("Error", "You have no permissions to do this!<br> <a href=./>Back to main</a>");
  }

  loadsmilies();

  $fieldlist=''; $ufields=array('posts','regdate','lastpost','lastview','location','rankset','title','usepic','head','sign');
  foreach($ufields as $field)
    $fieldlist.="u.$field u$field,";

  if($pid=$_GET['id'])
    checknumeric($pid);
// should display an error otherwise but for now who cares
//  if ($pid) 

    if (!$pid) {
      error("Error", "Private message does not exist. <br> <a href=./>Back to main</a>");
    }

    $pmsgs=$sql->fetchq("SELECT ".userfields('u','u').",$fieldlist p.*, pt.* "
                       ."FROM pmsgs p "
                       ."LEFT JOIN users u ON u.id=p.userfrom "
                       ."LEFT JOIN pmsgstext pt ON p.id=pt.id "
                       ."WHERE p.id=$pid");
    $tologuser=($pmsgs['userto']==$loguser['id']);

    if(((!$tologuser && $pmsgs['userfrom']!=$loguser['id']) && !has_perm('view-user-pms'))) {
      error("Error", "Private message does not exist. <br> <a href=./>Back to main</a>");
    }elseif($tologuser && $pmsgs['unread'])
      $sql->query("UPDATE pmsgs SET unread=0 WHERE id=$pid");

    pageheader($pmsgs['title']);

    $topbot=
          "$L[TBL] width=100%>
".        "  $L[TDn]><a href=./>Main</a> - <a href=private.php".(!$tologuser?"?id=".$pmsgs['userto']:'').">Private messages</a> - ". htmlval($pmsgs['title']) ."</td>
".        "  $L[TDnr]>
".        "    <a href=sendprivate.php?pid=$pid>Reply</a>
".        "  </td>
".        "$L[TBLend]
";

// Cleanup of undefined indexes.
    $pmsgs['id']=$pmsgs['deleted']=$pmsgs['user']=$pmsgs['thread']=$pmsgs['revision']=$pmsgs['maxrevision']=$pmsgs['num']=$pmsgs['num']=$pmsgs['tforum']=0;
    print "$topbot
".         threadpost($pmsgs,0)."
".        "$topbot
";

  pagefooter();
?>
