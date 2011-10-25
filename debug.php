<?php
ini_set('display_errors', 1);
require_once 'parsing.php';
require_once 'proxy.php';

$ch = curl_init();

$name = isset($_REQUEST["name"])?$_REQUEST["name"]:"";
$pw = isset($_REQUEST["pw"])?$_REQUEST["pw"]:"";
$realm = isset($_REQUEST["realm"])?$_REQUEST["realm"]:"";


$result = tribInit($name, $pw, $realm, $ch);

if ( $result === false )
	echo "Init failed: " . curl_error($ch);
else
	//echo "Init Cookies: {$result["cookies"]}<br /><br />Case number: {$result["case"]}<br /><br />";

$case = $result["case"];
$result = tribGetCase($case, $realm, $ch, $result["cookies"]);

if ( $result === false )
	echo "Case-get failed: " . curl_error($ch);
else {
	echo "Get Case Cookies: {$result["cookies"]}<br /><br />Number of games: {$result['numGames']} <br />";
	//echo "<br />Case html: <br />" . nl2br(htmlspecialchars($result["html"]));
}

echo "<br />GET GAME 1 <br />";

$gameData = tribGetGame($case, 1, $realm, $ch, $result["cookies"]);

echo nl2br(htmlspecialchars($gameData["JSON"]));

echo "<br />Captcha data: <br />";
$result = tribGetCaptcha($realm, $ch, $gameData["cookies"]);

if ( $result === false )
	echo "Captcha get failed: " . curl_error($ch);
else {

	$cookies = $result["cookies"];
	echo "<img src=\"{$result["captcha"]}\"><br />";
}

//Skip the case, get the next one
echo "<br />SKIPPING CASE<br />";

$result = tribSkipCase($case, $realm, $ch, $cookies);

if ( $result === false )
	echo "Case skip failed: " . curl_error($ch);
else {

	$cookies = $result["cookies"];
	echo "<br />New Case no: {$result["case"]} <br />";
}
