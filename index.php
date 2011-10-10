<?php
// We always use SSL
if (empty($_SERVER['https']))
{
	header('Location: https://tribunal.phpfogapp.com/');
	die;
}

// session expires in 30 minutes, only on our domain, only send session cookie over SSL
session_set_cookie_params(1800, '/', 'tribunal.phpfogapp.com', true);
session_start();

// if we don't know which case they're reviewing, they are not logged in
if (!isset($_SESSION['case']))
{
	require 'login.php';
}
else
{
	require 'review.php';
}
