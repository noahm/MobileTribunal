<?php //we only get here by having $_SESSION['case'] set
$message = '';
$caseInfo = array();
require 'proxy.php';
$ch = curl_init();

$captchaResult = isset($_REQUEST["captcha-result"])?$_REQUEST["captcha-result"]:"";
$formTokens = isset($_REQUEST["formTokens"])?$_REQUEST["formTokens"]:"";
$verdict = isset($_REQUEST["verdict"])?$_REQUEST["verdict"]:"";

if ( $verdict != "" )
{
	switch ( $verdict )
	{
		case "Punish":
		case "Pardon":
			$result = tribReviewCase($_SESSION["case"], json_decode($_SESSION['formTokens'], true), $verdict=="Punish", $captchaResult, $_SESSION["realm"], $ch, $_SESSION["cookies"]);
			break;
		case "Skip":
			$result = tribSkipCase($_SESSION["case"], $_SESSION["realm"], $ch, $_SESSION["cookies"]);
			break;
		default:
			$result = 0; //No verdict case, but won't evaluate as true
			break;

	}

	if ( $result )
	{
		$_SESSION["case"] = $result["caseno"];
		$_SESSION["cookies"] = $result["cookies"];
	}
	elseif ( $result === false ) //Submitted verdict but failed; differentiates from no verdict
		$message .= 'Failed to submit case verdict <br />';
	elseif ( $result == 0 )
		$message .= 'Invalid verdict <br />';

}

$result = tribGetCase($_SESSION['case'], $_SESSION['realm'], $ch, $_SESSION['cookies']);
if ($result)
{
	$_SESSION['cookies'] = $result['cookies'];
	$_SESSION['formTokens'] = $result['formTokens'];

	$message = 'You have ' . $result['numGames'] . ' games to review and your form tokens are ';
	$message .= htmlspecialchars($_SESSION['formTokens']);
	$message .= '<br />';

	$result = tribGetGame($_SESSION['case'], 1, $_SESSION['realm'], $ch, $_SESSION['cookies']);
	if ($result)
	{
		$_SESSION['cookies'] = $result['cookies'];
		//Parse game info here.
		$message .= "<br />Game JSON is: " . $result["JSON"] . "<br />";
	}
	else
		$message .= 'Failed to get Game 1<br />';

	$result = tribGetCaptcha($_SESSION['realm'], $ch, $_SESSION['cookies']);
	if ($result)
	{
		$_SESSION['cookies'] = $result['cookies'];
		$message .= "Captcha: <img src=\"{$result["captcha"]}\">";
		$message .= "<br />";
	}
	else
		$message .= 'Failed to get captcha<br />';
}
else
{
	$message = 'Failed to get case info';
}
curl_close($ch);
?>
<!DOCTYPE html>
<html>
<head>
	<?= htmlHead(); ?>
</head>
<body>
	<h1>Reviewing case #<?= $_SESSION['case'] ?></h1>
	<p><?= $message ?></p>
	<p>Rather than display this info, we will just load the first game and display that info here.</p>
	<br />
	<p>Eventually, this should be replaced by AJAX</p>
	<form name="tribunalverdict" method="post" action="/">
		<p>Captcha:</p>
		<input type="text" name="captcha-result">
		<br />
		<input type="submit" name="verdict" value="Punish" /><input type="submit" name="verdict" value="Pardon" /><input type="submit" name="verdict" value="Skip" />
	</form>
</body>
</html>
