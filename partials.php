<?php
define("BETA", true);
$betaUsers = array (
  'b4bbc1d3a3cdeb5bb41ec4c2cb65a1dd1a7a87d1',
  '84623f05082c43d16e90466fd60dff499905ba39',
  '425c051fd12adb6e5a7b12292d013dfa61515cb5',
  '8e1a05c861a453ab06f3fd47b322ddee68feac0a',
  'a4ae191ab7d3291b8ec0c5ff7eac3710bce489f5',
  '5ba674669c9812806f021a0c81f6a63f5fca360e',
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
  'b3c1ddbf5e7cd637e5bb97f98e1601bc158ea5f7',
  '61f0eab02874394590282533230833cfa02b889c',
);

// This file should contian re-usable html parts so we don't repeat often used code

function htmlHead() {
	return <<<HTML
	<title>Tribunal</title>
	<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0" />
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="stylesheet" type="text/css" href="assets/stylesheets/normalize.css">
	<link rel="stylesheet" type="text/css" href="assets/stylesheets/mobiletrib.css">
	<script type="text/javascript" src="assets/javascripts/jquery.min.js"></script>
	<script type="text/javascript" src="assets/javascripts/jquery.store.patched.js"></script>
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