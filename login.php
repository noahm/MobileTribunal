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
  '98c691b28bf52767cacf4459501dbb70d6f72eb9',
  'f6cd1bc7b9c6eaca8c3a2f6e661c0ea3b3119278',
  'ef6e140cfecabddcacef16a24844071a1276e6cb',
  '125d7214fa04ca774b20577128f25502d0f695bc',
  '35354d3611b5f1350da676e397452ce912cb1670',
  '25c8712db0e49dcae635a8c3e0ccf4106ca4a09f',
  'c35649e10b8c8cbdc1b48b930a41866733f31c69',
  '378973945320ebf056ac18050deb6c5498bb9b28',
  'ab0082d05c8799523142197636461adff06f2f82',
  '04fe079470c5b5f4d2565b259362612e70991111',
  '6262f7098084f2e6aac13ef1cf7cc4ec51659776',
  '318a3d24853ad93ebd0196744591488456e774c9',
  '397353dc175dc977340eb0f22b199c583a3ecedb',
  'e0cd77657fbbe70db44787cf834dec71905b58f0',
  'bb36f94befabebbae5f076b25e5b03ad7567ba90',
  '8435341471ab914c6f517f6105fd2c375950f815',
  'c71d9cf96f5f3595e79e91803bd53e16cb6c6499',
  '5f464ebb52082fed7afd77e098c94ca8101ef60f',
  '47ad855b9463b8876b03c6d278843ed6500a52b2',
  'c6e269f3b0cd2c1f76c115e25d1e416061d042ee',
  'facbf9909ab183d4298fdfb13ae251d865c05e77',
  'd3c38b028016a1ff2a2397a28865ecb48a618c9e',
  '952cb339e99b81493667f346be8efab58574c5c6',
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
