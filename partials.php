<?php
// This file should contian re-usable html parts so we don't repeat often used code

function htmlHead() {
	return <<<HTML
	<title>Tribunal Mobile</title>
	<meta name="viewport" content="initial-scale=1">
	<script>
	if (window.location.protocol !== 'https:') window.location.href = 'https://tribunal.phpfogapp.com/';
	</script>
HTML;
}
