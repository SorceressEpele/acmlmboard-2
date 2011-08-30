<?php
  require 'lib/common.php';
  if(!ismod()) {
    pageheader('Nothing here.');
  } else {
    pageheader('Mood avatars');

    $a=$sql->query("SELECT users.* FROM mood,users WHERE users.id=mood.user GROUP BY users.id ORDER BY users.id ASC");

    print "Mood avatars:
".        "$L[TBL1]>
".        "  $L[TRh]>
".        "    $L[TDh] width=30>ID</td>
".        "    $L[TDh] width=300>Username</td>
".        "    $L[TDh]>Mood avatars</td>
";

    for($i=1;$m=$sql->fetch($a);$i++){
      $tr=($i%2?'TR2':'TR3').'c';
      print
          "  $L[$tr]>
".        "    $L[TD]>$m[id].</td>
".        "    $L[TDl]>".userlink($m)."</td>
".        "    $L[TD]>
";
      $b=$sql->query("SELECT * FROM mood WHERE user=$m[id]");
      while($n=$sql->fetch($b)) echo "<img src='gfx/userpic.php?id=$n[user]_$n[id]' title='$n[label]'>";
    }
    print "$L[TBLend]
";
  }
  pagefooter();

?>
