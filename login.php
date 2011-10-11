<?php // the index will include this file if $_SESSION['case'] is not set
$feedback = array();
if (!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['realm']))
{
	// validate submission
	$validated = true;
	if (!in_array($_POST['realm'], array('na','euw','eune')))
	{
		$validated = false;
		$feedback[] = 'Region was invalid.';
	}

	if ($validated)
	{
		// save realm to the session
		$_SESSION['realm'] = $_POST['realm'];

		// perform login
		$ch = curl_init();
		require 'proxy.php';
		if ($result = tribInit($_POST['username'], $_POST['password'], $_SESSION['realm'], $ch))
		{
			// save important info
			$_SESSION['cookies'] = $result['cookies'];
			$_SESSION['case'] = $result['caseno'];
			// redirect back to the app
			curl_close($ch);
			header('Location: /');
			die;
		}
		else
		{
			curl_close($ch);
			$feedback[] = 'Riot rejected your login.';
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<?= htmlHead(); ?>
</head>
<body>
	<form method="post" action"/" autocapitalize="off" autocorrect="off"><fieldset>
		<legend>Login to access Tribunal Mobile</legend>
		<div><?= implode('<br>', $feedback) ?></div>
		<div><label for="realm">Region</label>
			<select name="realm" id="realm">
				<option value="na">North America</option>
				<option value="euw">EU West</option>
				<option value="eune">EU Nordic &amp; East</option>
			</select>
		</div>
		<div><label for="user">Username</label> <input name="username" id="username" type="text"></div>
		<div><label for="pass">Password</label> <input name="password" id="password" type="password"></div>
		<div><input type="submit" value="Login"></div>
	</fieldset></form>
</body>
</html>
