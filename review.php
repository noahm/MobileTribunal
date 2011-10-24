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
	<link rel="stylesheet" type="text/css" href="/assets/stylesheets/mobiletrib.css">
	<script type="text/javascript" src="/assets/javascripts/mobiletrib.js"></script>
</head>
<body>

<div id="title">
	<select id="game-selector">
		<option value="submit">Submit Verdict</option>
		<option selected value="1">Game 1</option>
		<option value="2">Game 2</option>
		<option value="3">Game 3</option>
	</select>
	<h2>Case #<span id="case-id">123456</span>:</h2>
	<h1 id="summoner-name"></h1>
</div>

<div id="loading">
	<img src="/assets/images/spinner.gif" style="vertical-align:middle"> Loading...
</div>

<div id="game">
	<div id="core-info">
		<section id="portrait">
			<img src="">
			<div id="level"></div>
		</section>
		<section id="inventory">
			<div id="inventory-container">
			</div>
		</section>
		<section id="stats">
			<div id="time-score">
				<span id="time"></span>
				<img src="/assets/images/kills.png"><span id="kills"></span>
				<img src="/assets/images/deaths.png"><span id="deaths"></span>
				<img src="/assets/images/assists.png"><span id="assists"></span>
			</div>
			<ul>
				<li><label>Outcome:</label><span id="outcome"></span></li>
				<li><label>Creep Score:</label><span id="creep-score"></span></li>
				<li><label>DPS Out:</label><span id="dps-out"></span></li>
				<li><label>DPS In:</label><span id="dps-in"></span></li>
			</ul>
		</section>
	</div>
	<h1>Reports (<span class="allied"><span id="allied-report-count"></span> allied</span>, <span class="enemy"><span id="enemy-report-count"></span> enemy</span>)</h1>
	<ul id="reports">
	</ul>
	<h1>
		Chat Log
		<select id="chat-filter">
			<option selected value="">Unfiltered</option>
			<option value="only-all">[All]</option>
			<option value="hide-enemy">Allied</option>
			<option value="hide-allied">Enemy</option>
			<option value="only-reported">Reported</option>
		</select>
		<label for="champ-only">ChampOnly</label>&nbsp;<input type="checkbox" name="champ-only" id="champ-only">
	</h1>
	<ol id="chat">
	</ol>
</div>

<div id="submit">
	<p id="timer-message">Please review the case for at least <time>60</time> more seconds before deciding your verdict.</p>
	<form>
		<img src="" id="captcha">
		<input type="button" id="refresh-captcha" alt="refresh" value="refresh"><br>
		<input type="text" id="captcha-input" name="captcha_result"><br><br>
		<input type="submit" id="punish" value="punish" name="op">
		<input type="submit" id="pardon" value="pardon" name="op">
		<input type="submit" id="skip"	 value="skip"	name="op">
	</form>
</div>

</body>
</html>
