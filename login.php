<?php // the index will include this file if $_SESSION['case'] is not set
$feedback = array();

define("BETA", true);
$betaUsers = array( '425c051fd12adb6e5a7b12292d013dfa61515cb5', '8e1a05c861a453ab06f3fd47b322ddee68feac0a' );

if (!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['realm']))
{
	// validate submission
	$validated = true;
	if (!in_array($_POST['realm'], array('na','euw','eune')))
	{
		$validated = false;
		$feedback[] = 'Region was invalid.';
	}

	if ( BETA && !in_array(sha1($_POST['username']), $betaUsers) )
	{
		$validated = false;
		$feedback[] = 'You do not have access to this beta test.';
	}

	if ($validated)
	{
		// save realm to the session
		$_SESSION['realm'] = $_POST['realm'];

		// perform login
		$ch = curl_init();
		require_once 'proxy.php';
		if ($result = tribInit($_POST['username'], $_POST['password'], $_SESSION['realm'], $ch))
		{
			// save important info
			$_SESSION['cookies'] = $result['cookies'];
			$_SESSION['case'] = $result['case'];
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
require 'assets/layouts/login.html';
