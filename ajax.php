<?php
require_once 'partials.php';
startSession();
require_once 'proxy.php';

$cmd = isset($_REQUEST["cmd"]) ? $_REQUEST["cmd"] : "";
$game = isset($_REQUEST["game"]) ? $_REQUEST["game"] : "";
$verdict = isset($_REQUEST["verdict"]) ? $_REQUEST["verdict"] : "";
$captchaResult = isset($_REQUEST["captcha-result"]) ? $_REQUEST["captcha-result"] : "";

//Some verification so we don't send bogus requests
if ( $cmd == "getGame" && $game == "" )
	die("0");

if ( $cmd == "sendVerdict" && (!preg_match('/^\w{4}$/',$captchaResult) || !preg_match('/^(punish|pardon)$/', $verdict)) )
	die("0");

$ch = curl_init();

switch ( $cmd )
{

	case "getCase":
		header('Content-Type: application/json');
		$result = tribGetCase($_SESSION['case'], $_SESSION['realm'], $ch, $_SESSION['cookies']);
		if ( $result === false )
			echo "0";
		else
		{
			$_SESSION['cookies'] = $result['cookies'];
			$_SESSION['formTokens'] = $result['formTokens'];
			echo json_encode(array('numGames'=>$result["numGames"], 'caseId'=>$_SESSION['case']));
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

	case "sendVerdict":
		//Check captcha first
		$result = tribCheckCaptcha($captchaResult, $_SESSION["realm"], $ch, $_SESSION["cookies"]);
		if ( $result === false )
			echo "0";
		else
		{

			$_SESSION["cookies"] = $result["cookies"];	//In case next request fails

			if ( $result["captchaResult"] != "1" )
				echo "failed";
			else
			{
	
				$result = tribReviewCase($_SESSION["case"], json_decode($_SESSION["formTokens"], true), $verdict=="punish", $captchaResult, $_SESSION["realm"], $ch, $result["cookies"]);

				if ( $result === false )
					echo "0";
				else
				{
					$_SESSION['cookies'] = $result['cookies'];
					$_SESSION['case'] = $result['case'];
					echo $result["case"];
				}

			}

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
