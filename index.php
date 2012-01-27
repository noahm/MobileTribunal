<?php
require_once 'support/config.php';
require_once 'support/partials.php';

if (FORCE_SSL && !usingSSL())
{
	header('Location: ' . getAbsolutePath() . '?secure');
	die;
}

startSession(); // only use this on the endpoint pages (index.php and ajax.php)

if ( isset($_REQUEST["logout"]) )
{

	$_SESSION = array();
	session_destroy();
	header('Location: ' . getAbsolutePath());
	die();
}

include 'assets/layouts/mobiletrib.html';
