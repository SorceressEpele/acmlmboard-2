<?php

require("lib/common.php");

if(isset($_GET['catch']))
{

	$monID = (int)$_GET['catch'];
	$userID = (int)$loguser['id'];
	
	if ($userID == 0) die("Not logged in.");
	// this can't use check_token() as it's meant to return text from an alert
	if ($_GET['auth'] != generate_token("{$userID}_{$monID}")) die("Not a valid capture.");
	
	require('lib/sprites.php');

	$sql->query("INSERT IGNORE INTO sprite_captures VALUES(".$userID.", ".$monID.")") or die("Could not register capture.");
	if($sql->affectedrows() == 1)
	{
		$monName = $sql->result($sql->query("SELECT name FROM sprites WHERE id=".$monID), 0, 0);
		$grats = "Congratulations. You caught ".$monName;
		
		//Granting a badge for catching N sprites
		$numCaught = $sql->result($sql->query("SELECT COUNT(*) FROM sprite_captures WHERE userid=".$userID), 0, 0);
		if($numCaught == 7)
		{
			//mysql_query("INSERT IGNORE INTO usertokens VALUES(".$userID.", 100)");
			//$grats .= " and got the Dodongo Badge!";
			$grats .= "!";
		}
		else
			$grats .= "!";
		
		die($grats);
	}
	die("OK");
}

pageheader();
if(!$log)
{
	print "
	$L[TBL1]>
		$L[TD1c]>
			You must be logged in to check your captured Sprites!<br>
			<a href=./>Back to main</a> or <a href=login.php>login</a>
	$L[TBLend]
";
	pagefooter();
	die();
}

$captureReq = $sql->query("SELECT monid FROM sprite_captures WHERE userid = ".$loguser['id']);
$captures = array();
while($capt = $sql->fetch($captureReq))
	$captures[$capt['monid']] = true;

$spritecateg = array();
$qspritecateg = $sql->query("SELECT `id`, `name` FROM `spritecateg`");

while ($allspcquery= $sql->fetch($qspritecateg))
{ 
  $spritecateg[$allspcquery['id']]= $allspcquery['name'];

}

$headers = array
(
	"id" => array //Entry key is used in $data to bind fields
	(
		"caption" => "#",
		"width" => "32px",
		"align" => "center",
		"color" => 1
	),
	"img" => array("caption"=>"Image", "width"=>"400px", "color"=>2),
	"name" => array("caption"=>"Name", "align"=>"center", "color"=>1),
	"franchiseid" => array("caption"=>"Franchise", "align"=>"center", "color"=>2),
	"flavor" => array("caption"=>"Description", "color"=>1),

	//Hidden flag could be used for admin-only columns.
	"secretbuttfun" => array("caption"=>"You can't see this one!", "hidden"=>true),
);

$data = array();
$monReq = $sql->query("SELECT * FROM sprites ORDER BY id ASC");
while($mon = $sql->fetch($monReq))
{
	if(isset($captures[$mon['id']]))
	{
		$pics = explode("|", $mon['pic']);
		$pic = $pics[0];
		$data[] = array
		(
			"id" => $mon['id'],
			"img" => "<img src=\"img/sprites/".$pic."\" title=\"".$mon['title']."\" alt=\"\" / style=\"max-width: 400px;\">",
			"name" => $mon['name'],
			"franchiseid" => $spritecateg[$mon['franchiseid']],
			"flavor" => $mon['flavor'],
		);
	}
	else
	{
		$data[] = array
		(
			"id" => $mon['id'],
			"img" => "&nbsp;",
			"name" => "???",
			"franchiseid" => "???",
			"flavor" => "&nbsp;"
		);
	}
}

$data[6]['secretbuttfun'] = "PONIES AND PONIES AND PONIES AND PONIES...";

RenderTable($data, $headers);

pagefooter();

?>
