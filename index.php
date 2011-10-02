<?php

include("parsing.php");
include("proxy.php");

$ch = curl_init();

$name = isset($_REQUEST["name"])?$_REQUEST["name"]:"";
$pw = isset($_REQUEST["pw"])?$_REQUEST["pw"]:"";
$realm = isset($_REQUEST["realm"])?$_REQUEST["realm"]:"";


$result = tribInit($name, $pw, $realm, $ch);

if ( $result === false )
	echo "Init failed: " . curl_error($ch);
else
	echo "Init Cookies: {$result["cookies"]}<br /><br />Case number: {$result["caseno"]}<br /><br />";
$caseno = $result["caseno"];
$result = tribGetCase($caseno, $realm, $ch, $result["cookies"]);

if ( $result === false )
	echo "Case-get failed: " . curl_error($ch);
else
	echo "Get Case Cookies: {$result["cookies"]}<br /><br />Number of games: " . getNumberOfGames($result["html"]) . "<br /> <br />Case html: <br />" . nl2br(htmlspecialchars($result["html"]));

echo "<br />GET GAME 1 <br />";

$result = tribGetGame($caseno, 1, $realm, $ch, $result["cookies"]);

echo nl2br(htmlspecialchars($result["JSON"]));

?>