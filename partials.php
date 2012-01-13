<?php
// This file should contian re-usable html parts so we don't repeat often used code

function htmlHead() {
	return <<<HTML
	<title>Tribunal</title>
	<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0" />
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="stylesheet" type="text/css" href="assets/stylesheets/normalize.css">
	<link rel="stylesheet" type="text/css" href="assets/stylesheets/mobiletrib.css">
	<script type="text/javascript" src="assets/javascripts/jquery.min.js"></script>
	<script type="text/javascript" src="assets/javascripts/jquery.store.js"></script>
	<script type="text/javascript" src="assets/javascripts/viewporter.js"></script>
	<link rel="shortcut icon" type="image/x-icon" href="assets/images/icons/favicon.ico" />
	<link rel="apple-touch-icon" media="screen and (resolution: 163dpi)" href="assets/images/icons/57.png" />
	<link rel="apple-touch-icon" media="screen and (resolution: 132dpi)" href="assets/images/icons/72.png" />
	<link rel="apple-touch-icon" media="screen and (resolution: 326dpi)" href="assets/images/icons/114.png" />

HTML;
}

function startSession() {
	// session expires in 30 minutes, only on our domain, only send session cookie over SSL
	session_set_cookie_params(1800, '/', $_SERVER['HTTP_HOST'], true);
	session_start();
}

function getAbsolutePath() {
	return (FORCE_SSL ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
}

?>