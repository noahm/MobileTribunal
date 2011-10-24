<?php
// This file should contian re-usable html parts so we don't repeat often used code

function htmlHead() {
	return <<<HTML
	<title>Tribunal Mobile</title>
	<meta name="viewport" content="width=device-width,user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="stylesheet" type="text/css" href="/assets/stylesheets/normalize.css">
	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
HTML;
}
