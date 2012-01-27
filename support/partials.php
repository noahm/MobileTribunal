<?php
function startSession() {
	// session expires in 30 minutes, only on our domain, only send session cookie over SSL
	$domain = $_SERVER['HTTP_HOST'];
	// strip a possible port number from the host
	if ($pos = strrpos($domain,':')) {
		$domain = substr($domain, 0, $pos);
	}
	session_set_cookie_params(1800, '/', $domain, FORCE_SSL);
	session_start();
}

function getAbsolutePath() {
	return (FORCE_SSL ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
}

function usingSSL() {
	return (
		(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1))
		||
		(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
	);
}
