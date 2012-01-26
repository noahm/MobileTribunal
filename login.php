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
		require_once 'support/proxy.php';
		if ($result = tribInit($_POST['username'], $_POST['password'], $_SESSION['realm'], $ch))
		{


			if ($result['case'] == "finished")
			{
				$feedback[] = 'You have done all the cases that Riot allows within a single day. The limit is reset nightly at 1:00 AM PDT';
				curl_close($ch);
			}
			elseif ($result['case'] == "level")
			{
				$feedback[] = 'You must be level 30 to participate in the Tribunal';
				curl_close($ch);
			}
			else
			{
				// save important info
				$_SESSION['cookies'] = $result['cookies'];
				$_SESSION['case'] = $result['case'];
				// redirect back to the app
				curl_close($ch);
				header('Location: ' . getAbsolutePath() . '?review');
				die;
			}

		}
		else
		{
			curl_close($ch);
			$feedback[] = 'Riot rejected your login.';
		}
	}
}
require 'assets/layouts/login.html';
