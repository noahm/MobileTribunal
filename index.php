<?php
// We always use SSL
if ($_SERVER['HTTP_X_FORWARDED_PROTO'] != 'https')
{
	header('Location: https://tribunal.phpfogapp.com/?secure');
	die;
}

require_once 'partials.php';

startSession(); // only use this on the endpoint pages (index.php and ajax.php)

if ( isset($_REQUEST["logout"]) )
{

	$_SESSION = array();
	session_destroy();

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
