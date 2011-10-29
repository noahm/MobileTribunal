<?php // the index will include this file if $_SESSION['case'] is not set
$feedback = array();

define("BETA", true);
$betaUsers = array (
  '425c051fd12adb6e5a7b12292d013dfa61515cb5',
  '8e1a05c861a453ab06f3fd47b322ddee68feac0a',
  'a4ae191ab7d3291b8ec0c5ff7eac3710bce489f5',
  'ad1d1b57253df8f8195e2e746c0a630815001b7e',
  'efa2b1e690b4a5a52c596e15f5529a34247d9249',
  '9a6d6ce13b119e83cdda9660f7d3d38002264f89',
);

if (!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['realm']))
{
	// validate submission
	$validated = true;
	if (!in_array($_POST['realm'], array('na','euw','eune')))
	{
		$validated = false;
		$feedback[] = 'Region was invalid.';
	}

	if ( BETA && !in_array(sha1(strtolower($_POST['username'])), $betaUsers) )
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
