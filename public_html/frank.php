<?php
    require 'lib/common.php';
    
    if (!has_perm('view-acs-calendar')) {
        error("Error", "You have no permissions to do this!<br> <a href=./>Back to main</a>");
    }
//Various variables.
  $time=86400;
  $kcscap=10;
  $kcspoints=array(1=>10,7,5,3,1,0);
  function dtime($ktc) {
    return $ktc+7200;
  }
//Code taken from the calendar
    $daynames = array('Sunday','Monday','Tuesday','Wednesday',
                      'Thursday','Friday','Saturday');
    $monthnames = array(1=>'January',  'February','March',   'April',
                           'May',      'June',   'July',    'August',
                           'September','October','November','December');
    $monthlen = array(1=>31,28,31,30,31,30,31,31,30,31,30,31);
    $today = getdate(ctime());
    
    if (isset($_REQUEST['m']) && is_numeric($_REQUEST['m'])) {
        $month = $_REQUEST['m'];
    } else {
        $month = $today['mon'];
    }
    if (isset($_REQUEST['y']) && is_numeric($_REQUEST['y'])) {
        $year = $_REQUEST['y'];
    } else {
        $year = $today['year'];
    }
    if (isset($_REQUEST['d']) && is_numeric($_REQUEST['d'])) {
        $day = $_REQUEST['d'];
    } else if ($year == $today['year'] && $month == $today['mon']) {
        $day = $today['mday'];
    } else {
        $day = 32; //We'll correct this after checking if the year is a leap year.
    }
        
    
    $mtstamp = mktime(0,0,0,$month,1,$year);
    $mdays = intval(date('t', $mtstamp));
    $wday = intval(date('w', $mtstamp));
    $monthlen[2]+=intval(date('L', $mtstamp)); //Check for Leap Year
    if($day==32) $day=$monthlen[$month];

    pageheader('Forum Rankings');
    print "$L[TBL] width=\"100%\">
".        "    $L[TR]>
".        "        $L[TDc] colspan=7 style=\"font-size:200%\">$monthnames[$month] $year</td>
".        "    </tr>
".        "    $L[TRh]>
";

    for ($w = 0; $w < 7; $w++) {//days of the week
        print "        $L[TDh] width=\"14%\">$daynames[$w]</td>\n";
    }
    
    print "    </tr>
".        "    $L[TR] style=\"height:80\">\n";

    for ($w = 0; $w < $wday; $w++) {//unused cells in the first week
        print "$L[TD]></td>";
    }

    for ($mday = 1; $mday <= $mdays; $mday++, $wday++) {//main day cells
        if ($wday > 6) {  //week wrap around
            $wday = 0;
            print "</tr>$L[TR] style=\"height:80\">\n";
        }
        $l = ($mday == $day) ? $L['TD1l'] : $L['TD2l'];
        print "$l width=\"14%\" valign=\"top\"><a href=\"frank.php?d=$mday&m=$month&y=$year\">$mday</a>";
//Query by-day here.
  $dstr=strtotime($mday.' '.$monthnames[$month].' '.$year);
  $query='SELECT posts,regdate,'.userfields().',SUM(num) num FROM ('
          .'SELECT u.posts,regdate,'.userfields('u').',CASE WHEN COUNT(*)>'.$kcscap.' THEN '.$kcscap.' ELSE COUNT(*) END num '
          .'FROM users u '
          .'LEFT JOIN posts p ON p.user=u.id '
          .'LEFT JOIN threads t ON t.id=p.thread '
          .'LEFT JOIN forums f ON f.id=t.forum '
          .'WHERE p.date>'.($dstr-(dtime($dstr)%86400)).' AND p.date<'.($dstr-(dtime($dstr)%86400-86400)).' '
          .'AND f.private=0 AND p.deleted=0 '
          .'GROUP BY p.thread,u.id'
	.') inter GROUP BY id ORDER BY num DESC';
  $users=$sql->query($query);
  $pqry=@$sql->result($sql->query("SELECT count(*) FROM posts WHERE date>".($dstr-(dtime($dstr)%86400))." AND date<".($dstr-(dtime($dstr)%86400-86400))),0,0);

            print " -- <i>Total Posts: $pqry</i><table>";
 $q=1; $p=-1;
  for($i=1;$user=$sql->fetch($users);$i++){
    if($user['num']!=$p) $q=$i;
    if($q<=5) {
    if($mday <= $day){
	$uid=$user['id'];
	if(isset($points[$uid])) $points[$uid]=$points[$uid]+$kcspoints[$q]; else $points[$uid]=$kcspoints[$q];
    }
    print
	"<tr><td>$q</td><td>".userlink($user)."</td><td>$user[num]</td></tr>";
    $p=$user['num'];
  }
}
        print "</table></td>\n";
        
    }
    
    for (;$wday <= 6; $wday++) { //unused cells in the last week
        print "$L[TD]></td>";
    }
    
    print "    </tr>
".        "    $L[TR]>
".        "        $L[TDc] colspan=7> Month:";
    
    for ($i = 1; $i <= 12; $i++) {//month links
        if ($i == $month) {
            print " $i\n";
        } else {
            print " <a href=\"frank.php?m=$i&amp;y=$year\">$i</a>\n";
        }
    }
    
    print "             | Year:\n";
    
    for ($i = $year-2; $i <= $year+2; $i++) {//year links
        if ($i == $year) {
            print " $i\n";
        } else {
            print " <a href=\"frank.php?m=$month&amp;y=$i\">$i</a>\n";
        }
    }
    
    print "        </td>
".        "    </tr>
".         $L['TBLend'];

  //The old calendar ends here. Write the report!.
  //And the same query here for the selected date.
  $dstr=strtotime($day.' '.$monthnames[$month].' '.$year);
  $query='SELECT posts,regdate,'.userfields().',SUM(num) num FROM ('
          .'SELECT u.posts,regdate,'.userfields('u').',CASE WHEN COUNT(*)>'.$kcscap.' THEN '.$kcscap.' ELSE COUNT(*) END num '
          .'FROM users u '
          .'LEFT JOIN posts p ON p.user=u.id '
          .'LEFT JOIN threads t ON t.id=p.thread '
          .'LEFT JOIN forums f ON f.id=t.forum '
          .'WHERE p.date>'.($dstr-(dtime($dstr)%86400)).' AND p.date<'.($dstr-(dtime($dstr)%86400-86400)).' '
          .'AND f.private=0 AND p.deleted=0 '
          .'GROUP BY p.thread,u.id'
	.') inter GROUP BY id ORDER BY num DESC';
  $users=$sql->query($query);
  $pqry=@$sql->result($sql->query("SELECT count(*) FROM posts WHERE date>".($dstr-(dtime($dstr)%86400))." AND date<".($dstr-(dtime($dstr)%86400-86400))),0,0);
	print "$L[TBL] width=\"100%\">
".        "    $L[TRh]>
".        "        $L[TDc] colspan=2>KCS Report for $monthnames[$month] $year</td>
".        "    </tr>
".        "    $L[TR]>
$L[TD2l]>".strtoupper($monthnames[$month])." $day<hr style=\"width: 100px; margin-left: 0px;\">Total amount of posts: $pqry<br><br><table cellspacing=0>";
$report=strtoupper($monthnames[$month])." $day<hr style=\"width: 100px; margin-left: 0px;\">Total amount of posts: $pqry<br><br><table cellspacing=0>";
//Results for posts
 $q=1; $p=-1;
  for($i=1;$user=$sql->fetch($users);$i++){
    if($user['num']!=$p) $q=$i;
    if($q<=5) {
    $usr=$user['id'];
    $dpur[$usr]=$kcspoints[$q];
    $report.="<tr><td>$q</td><td>[user=$usr]</td><td>$user[num]</td></tr>";
    print "<tr><td>$q</td><td>".userlink($user)."</td><td>$user[num]</td></tr>";
    $p=$user['num'];
  }
}
$report.="</table><br><br>Daily Points<hr style=\"width: 100px; margin-left: 0px;\"><table cellspacing=0>";
print "</table><br><br>Daily Points<hr style=\"width: 100px; margin-left: 0px;\"><table cellspacing=0>";
//Daily Points
if(isset($dpur)){
arsort($dpur);
$r=0; $q=1; $t=9999;
foreach($dpur as $usr => $pnts){
	if($pnts<$t) $r=$q;
    	$mpur = $sql->fetch($sql->query("SELECT ".userfields()." FROM users WHERE id=$usr"));
	$report.="<tr><td>$r</td><td>[user=$usr]</td><td>$pnts</td></tr>";
	print "<tr><td>$r</td><td>".userlink($mpur)."</td><td>$pnts</td></tr>";
	$q++; $t=$pnts;
}
}
//Monthly Points
$report.="</table><br><br>Monthly Points<hr style=\"width: 100px; margin-left: 0px;\"><table cellspacing=0>";
print "</table><br><br>Monthly Points<hr style=\"width: 100px; margin-left: 0px;\"><table cellspacing=0>";
if(isset($points)){
arsort($points);
$r=0; $q=1; $t=9999;
foreach($points as $usr => $pnts){
	if($pnts<$t) $r=$q;
    	$mpur = $sql->fetch($sql->query("SELECT ".userfields()." FROM users WHERE id=$usr"));
	$report.="<tr><td>$r</td><td>[user=$usr]</td><td>$pnts</td></tr>";
	print "<tr><td>$r</td><td>".userlink($mpur)."</td><td>$pnts</td></tr>";
	$t=$pnts;
	$q++;
}
}
    $report.="</table>";
  print   "</table></td>$L[TD1l] style=\"width: 50%\" valign=\"top\">
".        "<textarea style=\"width: 100%; height: 400px;\" readonly=\"readonly\">$report</textarea></td>
".        "    </tr>
".         $L['TBLend'];
    pagefooter();
?>