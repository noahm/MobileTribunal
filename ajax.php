<?php
require 'proxy.php';

$cmd = isset($_POST["cmd"]) ? $_POST["cmd"] : "";
$game = isset($_POST["game"]) ? $_POST["game"] : "";
$captchaResult = isset($_POST["captcha-result"]) ? $_POST["captcha-result"] : "";

//Some verification so we don't send bogus requests
if ( $cmd == "getGame" && $game == "" )
	die("0");

//This will be replaced by proper captcha verification
if ( ( $cmd == "sendPunish" || $cmd == "sendPardon" ) && $captchaResult == "" )
	die("0");

$ch = curl_init();

switch ( $cmd )
{

	case "getCase":
		header('Content-Type: text/plain');
		$result = tribGetCase($_SESSION['case'], $_SESSION['realm'], $ch, $_SESSION['cookies']);
		if ( $result === false )
			echo "0";
		else
		{
			$_SESSION['cookies'] = $result['cookies'];
			$_SESSION['formTokens'] = $result['formTokens'];
			echo $result["numGames"];
		}
		break;

	case "getGame":
		header('Content-Type: application/json');
		$result = tribGetGame($_SESSION['case'], $game, $_SESSION['realm'], $ch, $_SESSION['cookies']);
		if ( $result === false )
			echo "0";
		else
		{
			$_SESSION['cookies'] = $result['cookies'];
			echo $result["JSON"];
		}
		break;

	case "getCaptcha":
		header('Content-Type: text/plain');
		$result = tribGetCaptcha($_SESSION['realm'], $ch, $_SESSION['cookies']);
		if ( $result === false )
			echo "0";
		else
		{
			$_SESSION['cookies'] = $result['cookies'];
			echo $result["captcha"];
		}
		break;

	case "sendPunish":
	case "sendPardon":
		$result = tribReviewCase($_SESSION["case"], json_decode($_SESSION["formTokens"], true), $cmd=="sendPunish", $captchaResult, $_SESSION["realm"], $ch, $_SESSION["cookies"]);
		if ( $result === false )
			echo "0";
		else
		{
			$_SESSION['cookies'] = $result['cookies'];
			$_SESSION['case'] = $result['case'];
			echo $result["case"];
		}
		break;

	case "sendSkip":
		$result = tribSkipCase($_SESSION["case"], $_SESSION["realm"], $ch, $_SESSION["cookies"]);
		if ( $result === false )
			echo "0";
		else
		{
			$_SESSION['cookies'] = $result['cookies'];
			$_SESSION['case'] = $result['case'];
			echo $result["case"];
		}
		break;
	default:
		echo "0"; //Should never get here

}

curl_close($ch);
