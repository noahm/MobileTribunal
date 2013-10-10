<?php
/* Copyright (c) 2012 kayson (kaysond) & Noah Manneschmidt (psoplayer)
 * https://github.com/noahm/MobileTribunal
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require_once 'support/config.php';
require_once 'support/partials.php';
header('Content-Type: application/json');
startSession();
require_once 'support/proxy.php';

if (FORCE_SSL && !usingSSL()) die;



$cmd = isset($_REQUEST["cmd"]) ? $_REQUEST["cmd"] : "";
$game = isset($_REQUEST["game"]) ? $_REQUEST["game"] : "";
$verdict = isset($_REQUEST["verdict"]) ? $_REQUEST["verdict"] : "";
$captchaResult = isset($_REQUEST["captcha-result"]) ? $_REQUEST["captcha-result"] : "";

if ($cmd == 'login') {
	$username = isset($_REQUEST["username"]) ? $_REQUEST["username"] : "";
	$password = isset($_REQUEST["password"]) ? $_REQUEST["password"] : "";
	$recaptcha_challenge = isset($_REQUEST["recaptcha_challenge"]) ? $_REQUEST["recaptcha_challenge"] : "";
	$recaptcha_response = isset($_REQUEST["recaptcha_response"]) ? $_REQUEST["recaptcha_response"] : "";
	$realm = isset($_REQUEST["realm"]) ? $_REQUEST["realm"] : "";
}

//Some verification so we don't send bogus requests
if ( $cmd == "getGame" && $game == "" )
{
	echo '{"status":"failed"}';
	return;
}

if ( $cmd != 'login' && $cmd != 'getRecaptcha' && (!isset($_SESSION['cookies']) || !isset($_SESSION['realm']) || !isset($_SESSION['case'])) )
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

	case 'getRecaptcha':
		$result = tribGetRecaptcha($ch, "na");
		if ( $result === false )
			echo '{"status":"failed"}';
		else
			echo json_encode($result);
		break;

	case 'login':
		$feedback = array();
		if (empty($username) || empty($password) || empty($realm) || empty($recaptcha_response) ) {
			$feedback[] = 'You must fill out all fields.';
		} else {
			// validate submission
			$validated = true;
			if (!in_array($realm, array('na','euw','eune','oce','br')))
			{
				$validated = false;
				$feedback[] = 'Region was invalid.';
			}

			if ( RESTRICT_USERS && !in_array(sha1(strtolower($username)), $users) )
			{
				$validated = false;
				$feedback[] = 'You do not have access to this beta test.';
			}

			if ($validated)
			{
				// save realm to the session
				$_SESSION['realm'] = $realm;

				// perform login
				$result = tribLogin($username, $password, $realm, $recaptcha_challenge, $recaptcha_response, $ch);
				if ( $result === false )
					$feedback[] = 'An unknown error occurred during login';
				elseif( $result["status"] != "ok" )
					$feedback[] = $result["status"]=="userpass"?'Invalid username/password':'Incorrect recaptcha response';
				else {
					$result = tribInit($realm, $result['cookies'], $ch);
					if ( $result === false )
						$feedback[] = 'Login succeeded but an unknown error occurred while starting the Tribunal';
					else {
						// save important info
						$_SESSION['cookies'] = $result['cookies'];
						$_SESSION['case'] = $result['case'];
						switch ($result['case'])
						{
							case 'finished':
							echo '{"status":"finished"}';
							break;
	
							case 'underlevel':
							echo '{"status":"underlevel"}';
							break;
	
							case 'recess':
							echo '{"status":"recess"}';
							break;
	
							case 'unknown':
							echo '{"status":"unknown"}';
							break;

							default:
							echo '{"status":"ok","case":"' . $result["case"] . '","numGames":' . $result["numGames"] . ',"votesToday":' . $result["votesToday"] . ',"votesAllowed":' . $result["votesAllowed"] . '}';
							break;
						}
					}
				}
			}
		}
		if( !empty($feedback) )
			echo json_encode(array( 'status' => 'error', 'feedback' => $feedback));
		break;

	case "getCase":
		$result = tribGetCase($_SESSION['realm'], $ch, $_SESSION['cookies']);
		if ( $result === false )
			echo '{"status":"failed"}';
		else
		{
			$_SESSION['cookies'] = $result['cookies'];
			$_SESSION['case'] = $result['case'];
			if ( $result['case'] == "finished" ) {
				echo '{"status":"finished"}';
			} elseif ($result['case'] == "recess") {
				echo '{"status":"recess"}';
			} else {
				echo '{"status":"ok","case":"' . $result["case"] . '","numGames":' . $result["numGames"] . ',"votesToday":' . $result["votesToday"] . ',"votesAllowed":' . $result["votesAllowed"] . '}';
			}
		}
		break;

	case "getGame":
		$result = tribGetGame($_SESSION['case'], $game, $_SESSION['realm'], $ch, $_SESSION['cookies']);
		if ( $result === false )
			echo '{"status":"failed"}';
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
		//$result = tribCheckCaptcha($captchaResult, $_SESSION["realm"], $ch, $_SESSION["cookies"]);
		if ( false )
			echo '{"status":"failed"}';
		else
		{

			//$_SESSION["cookies"] = $result["cookies"];	//In case next request fails

			if ( true ) //$result["captchaResult"] == "1"
			{
				$result = tribReviewCase(
					$_SESSION["case"], $verdict=="punish", "", $_SESSION["realm"], $ch, $_SESSION["cookies"]
					//$_SESSION["case"], $verdict=="punish", "", $_SESSION["realm"], $ch, $result["cookies"]
				);

				if ( $result === false )
					echo '{"status":"failed"}';
				else
				{
					$_SESSION['cookies'] = $result['cookies'];
					$_SESSION['case'] = $result['case'];
					if ( $result['case'] == "finished" )
						echo '{"status":"finished"}';
					else
						echo '{"status":"ok","case":"' . $result["case"] . '","numGames":' . $result["numGames"] . ',"votesToday":' . $result["votesToday"] . ',"votesAllowed":' . $result["votesAllowed"] . '}';
				}

			}
			else
				echo '{"status":"captchafail"}';
		}
		break;

	case "sendSkip":
		$result = tribSkipCase($_SESSION["case"], $_SESSION["realm"], $ch, $_SESSION["cookies"]);
		if ( $result === false )
			echo '{"status":"failed"}';
		else
		{
			$_SESSION['cookies'] = $result['cookies'];
			$_SESSION['case'] = $result['case'];
			if ( $result['case'] == "finished" )
				echo '{"status":"finished"}';
			else
				echo '{"status":"ok","case":"' . $result["case"] . '","numGames":' . $result["numGames"] . ',"votesToday":' . $result["votesToday"] . ',"votesAllowed":' . $result["votesAllowed"] . '}';
		}
		break;
	default:
		echo '{"status":"failed"}'; //Should never get here

}

if (isset($ch)) curl_close($ch);
