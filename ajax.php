<?php
require_once 'partials.php';
startSession();
require_once 'proxy.php';

$cmd = isset($_REQUEST["cmd"]) ? $_REQUEST["cmd"] : "";
$game = isset($_REQUEST["game"]) ? $_REQUEST["game"] : "";
$verdict = isset($_REQUEST["verdict"]) ? $_REQUEST["verdict"] : "";
$captchaResult = isset($_REQUEST["captcha-result"]) ? $_REQUEST["captcha-result"] : "";
$username = isset($_REQUEST["username"]) ? $_REQUEST["username"] : "";
$password = isset($_REQUEST["password"]) ? $_REQUEST["password"] : "";
$realm = isset($_REQUEST["realm"]) ? $_REQUEST["realm"] : "";

//Some verification so we don't send bogus requests
if ( $cmd == "getGame" && $game == "" )
{
	header('Content-Type: application/json');
	die("0");
}

if ( !isset($_SESSION['cookies']) || !isset($_SESSION['realm']) || !isset($_SESSION['case']) )
{
	header('Content-Type: application/json');
	die('"nosess"');
}

if ( $cmd == "login" && ( $username == "" || $password == "" || !in_array($realm, array('na','euw','eune')) ) )
{
	header('Content-Type: application/json');
	die("0");
}

$ch = curl_init();

switch ( $cmd )
{

	case "login":
		header('Content-Type: application/json');

		// save realm to the session
		$_SESSION['realm'] = $_POST['realm'];

		// perform login
		$ch = curl_init();

		if ($result = tribInit($username, $password, $realm, $ch))
		{

			if ($result['case'] == "finished" || $result['case'] == "level")
			{
				echo json_encode(array('caseId'=>$result['case']));
				curl_close($ch);
			}
			else
			{
				// save important info
				$_SESSION['cookies'] = $result['cookies'];
				$_SESSION['case'] = $result['case'];
				echo json_encode(array('caseId'=>$result['case']));

				curl_close($ch);
			}

		}
		else
		{
			curl_close($ch);
			echo json_encode(array('caseId'=>'fail'));
		}
		break;


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

			if ( $result["captchaResult"] == "1" )
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
			else
				echo "failed";
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
