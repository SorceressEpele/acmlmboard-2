<?php
  if($_GET[s])
    $file='../userpic/s'.$_GET[id];
  else
    $file='../userpic/'.$_GET[id];

  if((is_numeric($id) || preg_match("/\d+_\d\d?/", $id)) && file_exists($file))
    Header("Location:$file");
  else
    Header("Location:../img/_.png");
?>