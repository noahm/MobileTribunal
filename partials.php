<?php
// This file should contian re-usable html parts so we don't repeat often used code

function htmlHead() {
	return <<<HTML
	<title>Tribunal</title>
	<meta name="viewport" content="width=device-width,height=device-height,user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="stylesheet" type="text/css" href="assets/stylesheets/normalize.css">
	<link rel="stylesheet" type="text/css" href="assets/stylesheets/mobiletrib.css">
	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" src="assets/javascripts/jquery.store.js"></script>
	<link rel="shortcut icon" type="image/x-icon" href="assets/images/icons/favicon.ico" />
	<link rel="apple-touch-icon" media="screen and (resolution: 163dpi)" href="assets/images/icons/57.png" />
	<link rel="apple-touch-icon" media="screen and (resolution: 132dpi)" href="assets/images/icons/72.png" />
	<link rel="apple-touch-icon" media="screen and (resolution: 326dpi)" href="assets/images/icons/114.png" />

HTML;
}

function startSession() {
	// session expires in 30 minutes, only on our domain, only send session cookie over SSL
	session_set_cookie_params(1800, '/', 'tribunal.phpfogapp.com', true);
	session_start();
}
