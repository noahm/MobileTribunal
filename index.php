<?php
// We always use SSL
define('FORCE_SSL', true);

require_once 'partials.php';

if (FORCE_SSL && $_SERVER['HTTP_X_FORWARDED_PROTO'] != 'https')
{
	header('Location: ' . getAbsolutePath() . '?secure');
	die;
}

startSession(); // only use this on the endpoint pages (index.php and ajax.php)

if ( isset($_REQUEST["logout"]) )
{

	$_SESSION = array();
	session_destroy();
	header('Location: ' . getAbsolutePath() . '?login');
	die();
}

// if we don't know which case they're reviewing, they are not logged in
if (!isset($_SESSION['case']))
{
	require 'login.php';
}
else
{
	require 'review.php';
}
?>
