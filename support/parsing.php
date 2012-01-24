<?php
function tribParseHTML($html)
{
	// grab the element from the html
	$doc = new DOMDocument();

	// gag error reporting for all the nonsense that bad html pages generate during parsing
	$orig = error_reporting(0);
	$doc->loadHTML($html);
	error_reporting($orig);

	return array( 'numGames' => getNumGames($doc), 'formTokens' => getFormTokens($doc) );
}

function getNumGames($doc)
{
	$gamecount = $doc->getElementById('h_gamecount');
	return (int) $gamecount->textContent;
}

function getFormTokens($doc)
{
	$xpath = new DOMXpath($doc);
	return array(
		'form_build_id' => $xpath->query("//input[@name='form_build_id']/@value")->item(0)->value,
		'form_token' => $xpath->query("//input[@name='form_token']/@value")->item(0)->value,
		'form_id' => $xpath->query("//input[@name='form_id']/@value")->item(0)->value,
	);
}

function tribParseLocation($header, $realm)
{

	if ( stristr($header, "Location: http://$realm.leagueoflegends.com/tribunal/finished\r\n") )
		return "finished";

	else
	{
		$pattern = "/Location: http:\/\/$realm\.leagueoflegends\.com\/tribunal\/case\/([0-9]*)\/review\r\n/isU";
		if ( preg_match($pattern, $header, $matches) != 0 )
			return $matches[1];
		else
			return false;

	}

}