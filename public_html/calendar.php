<?php
    require 'lib/common.php';
    
    if (!has_perm('view-calendar')) {
        error("Error", "You have no permissions to do this!<br> <a href=./>Back to main</a>");
    }

    $daynames = array('Sunday','Monday','Tuesday','Wednesday',
                      'Thursday','Friday','Saturday');
    $monthnames = array(1=>'January',  'February','March',   'April',
                           'May',      'June',   'July',    'August',
                           'September','October','November','December');
    
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
        $day = 0;
    }
        
    
    $mtstamp = mktime(0,0,0,$month,1,$year);
    $mdays = intval(date('t', $mtstamp));
    $wday = intval(date('w', $mtstamp));
    
    //Fetch birthdays - uses the same crude but effective method as the old one
    $bdaytext = array();
    $bdayres = $sql->query("SELECT ".userfields().",birth FROM users WHERE birth != -1");
    while ($bdayarr = $sql->fetch($bdayres)) {
        $bdaydecode=explode('-',$bdayarr['birth']);

        if ($bdaydecode[0] == $month) {
            $age = $year - $bdaydecode[2];
            $t = userlink($bdayarr);
            if ($age > 0 && !$bdaydecode['2'] <= 0) {
                $t .= " turns $age";
            } else if ($bdaydecode['2'] <= 0) {
                $t .= "'s birthday";
            } else if ($age < 0) {
                $t .= " is born in ".(-$age)." year".(($age!=-1)?'s':'');
            } else {
                $t .= " is born";
            }
            if (isset($bdaytext[$bdaydecode[1]])) {
                $bdaytext[$bdaydecode[1]] .= '<br/>'.$t;
            } else {
                $bdaytext[$bdaydecode[1]] = $t;
            }
        }
    }
 
     //Fetch events - yet again, uses the same crude but effective method as the old one
    $eventtext = array();
    $eventres = $sql->query("SELECT * FROM events e LEFT JOIN users u ON u.id=e.user WHERE year = '$year' AND month = $month");

    while ($eventarr = $sql->fetch($eventres)) {
        $text = "<i>".$eventarr['event_title']."</i>".($eventarr['private']?"":" - ".userlink($eventarr));
        $eventtext[$eventarr['day']] = $text;
    }

    pageheader('Calendar');
    print "$L[TBL1] width=\"100%\">
".        "    $L[TR]>
".        "        $L[TD1c] colspan=7 style=\"font-size:200%\">$monthnames[$month] $year</td>
".        "    </tr>
".        "    $L[TRh]>
";

    for ($w = 0; $w < 7; $w++) {//days of the week
        print "        $L[TDh] width=\"14%\">$daynames[$w]</td>\n";
    }
    
    print "    </tr>
".        "    $L[TR] style=\"height: 80px;\">\n";

    if ($wday > 0) {//unused cells in the first week
        print "$L[TD2] colspan=\"".$wday."\"></td>";
    }

    for ($mday = 1; $mday <= $mdays; $mday++, $wday++) {//main day cells
        if ($wday > 6) {  //week wrap around
            $wday = 0;
            print "</tr>$L[TR] style=\"height: 80px\">\n";
        }
        $l = ($mday == $day) ? $L['TD3l'] : (($wday==0 || $wday==6) ? $L['TD2l'] : $L['TD1l']);
        print "$l width=\"14%\" valign=\"top\">$mday";
        $dnum=str_pad($mday,2,"0",STR_PAD_LEFT);
        if (isset($bdaytext[$dnum])) {
            print "<br/>$bdaytext[$dnum]";
        }
        if (isset($eventtext[$dnum])) {
            print "<br/>$eventtext[$dnum]";
        }
        print "</td>\n";
        
    }
    
    if ($wday < 6) { //unused cells in the last week
        print "$L[TD2] colspan=\"".(7-$wday)."\"></td>";
    }
    
    print "    </tr>
".        "    $L[TR]>
".        "        $L[TD1c] colspan=7> Month:";
    
    for ($i = 1; $i <= 12; $i++) {//month links
        if ($i == $month) {
            print " $i\n";
        } else {
            print " <a href=\"calendar.php?m=$i&amp;y=$year\">$i</a>\n";
        }
    }
    
    print "             | Year:\n";
    
    for ($i = $year-2; $i <= $year+2; $i++) {//year links
        if ($i == $year) {
            print " $i\n";
        } else {
            print " <a href=\"calendar.php?m=$month&amp;y=$i\">$i</a>\n";
        }
    }
    
    print "        </td>
".        "    </tr>
".         $L['TBLend'];
    
        

    pagefooter();
?>