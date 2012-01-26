<?php
require_once 'support/partials.php';
startSession();
require_once 'support/proxy.php';

if (FORCE_SSL && !usingSSL()) die;

header('Content-Type: application/json');

$cmd = isset($_REQUEST["cmd"]) ? $_REQUEST["cmd"] : "";
$game = isset($_REQUEST["game"]) ? $_REQUEST["game"] : "";
$verdict = isset($_REQUEST["verdict"]) ? $_REQUEST["verdict"] : "";
$captchaResult = isset($_REQUEST["captcha-result"]) ? $_REQUEST["captcha-result"] : "";

if ($cmd == 'login') {
	$username = isset($_REQUEST["username"]) ? $_REQUEST["username"] : "";
	$password = isset($_REQUEST["password"]) ? $_REQUEST["password"] : "";
	$realm = isset($_REQUEST["realm"]) ? $_REQUEST["realm"] : "";
}

//Some verification so we don't send bogus requests
if ( $cmd == "getGame" && $game == "" )
{
	echo '{}';
	return;
}

if ( $cmd != 'login' && (!isset($_SESSION['cookies']) || !isset($_SESSION['realm']) || !isset($_SESSION['case'])) )
{
	die('{"status":"nosess"}');
	return;
}

$ch = curl_init();

switch ( $cmd )
{

	case 'logout':
		$_SESSION = array();
		session_destroy();
		echo json_encode(array('status' => 'ok'));
		break;

	case 'login':
		$feedback = array();
		if (empty($username) || empty($password) || empty($realm)) {
			$feedback[] = 'You must fill out all fields.';
		} else {
			// validate submission
			$validated = true;
			if (!in_array($realm, array('na','euw','eune')))
			{
				$validated = false;
				$feedback[] = 'Region was invalid.';
			}

			if ( BETA && !in_array(sha1(strtolower($username)), $betaUsers) )
			{
				$validated = false;
				$feedback[] = 'You do not have access to this beta test.';
			}

			if ($validated)
			{
				// save realm to the session
				$_SESSION['realm'] = $realm;

				// perform login
				if ($result = tribInit($username, $password, $_SESSION['realm'], $ch))
				{
					// save important info
					$_SESSION['cookies'] = $result['cookies'];
					$_SESSION['case'] = $result['case'];
					echo '{"status":"ok"}';
					break;
				}
				else
				{
					$feedback[] = 'Riot rejected your login.';
				}
			}
		}
		echo json_encode(array( 'status' => 'error', 'feedback' => $feedback));
		break;

	case "getCase":
		if ( $_SESSION['case'] == "finished" )  //if user logs in but has already hit the limit
		{
			echo json_encode(array('numGames'=> 0, 'caseId'=>'finished', 'status'=>'finished'));
			break;
		}

		$result = tribGetCase($_SESSION['case'], $_SESSION['realm'], $ch, $_SESSION['cookies']);
		if ( $result === false )
			echo '{}';
		else
		{
			$_SESSION['cookies'] = $result['cookies'];
			$_SESSION['formTokens'] = $result['formTokens'];
			echo json_encode(array('numGames'=>$result["numGames"], 'caseId'=>$_SESSION['case']));
		}
		break;

	case "getGame":
		$result = tribGetGame($_SESSION['case'], $game, $_SESSION['realm'], $ch, $_SESSION['cookies']);
		if ( $result === false )
			echo '{}';
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
			echo '0';
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
			echo '{}';
		else
		{

			$_SESSION["cookies"] = $result["cookies"];	//In case next request fails

			if ( $result["captchaResult"] == "1" )
			{
	
				$result = tribReviewCase($_SESSION["case"], json_decode($_SESSION["formTokens"], true), $verdict=="punish", $captchaResult, $_SESSION["realm"], $ch, $result["cookies"]);

				if ( $result === false )
					echo '{}';
				else
				{
					$_SESSION['cookies'] = $result['cookies'];
					$_SESSION['case'] = $result['case'];
					echo json_encode($result["case"]);
				}

			}
			else
				echo '{"status":"failed"}';
		}
		break;

	case "sendSkip":
		$result = tribSkipCase($_SESSION["case"], $_SESSION["realm"], $ch, $_SESSION["cookies"]);
		if ( $result === false )
			echo '{}';
		else
		{
			$_SESSION['cookies'] = $result['cookies'];
			$_SESSION['case'] = $result['case'];
			echo json_encode($result["case"]);
		}
		break;
	default:
		echo '{}'; //Should never get here

}

if (isset($ch)) curl_close($ch);
