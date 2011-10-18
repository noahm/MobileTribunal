<?php // we only get here by having $_SESSION['case'] set
$message = '';
$caseInfo = array();
require 'proxy.php';
$ch = curl_init();
$result = tribGetCase($_SESSION['case'], $_SESSION['realm'], $ch, $_SESSION['cookies']);
if ($result)
{
	$_SESSION['cookies'] = $result['cookies'];
	require 'parsing.php';
	$caseInfo = tribParseHTML($result['html']);

	$message = 'You have ' . $caseInfo['numGames'] . ' games to review and your form tokens are ';
	$message .= htmlspecialchars(json_encode($caseInfo['formTokens']));
	$message .= '<br />';

	$game = tribGetGame($_SESSION['case'], 1, $_SESSION['realm'], $ch, $_SESSION['cookies']);
	if ($game)
	{
		$_SESSION['cookies'] = $result['cookies'];
		//Parse game info here.
		$message .= "<br />Game JSON is: " . $game["JSON"] . "<br />";
	}
	else
		$message .= 'Failed to get Game 1';
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
</body>
</html>
