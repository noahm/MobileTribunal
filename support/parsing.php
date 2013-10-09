<?php
/* Copyright (c) 2012 kayson (kaysond) & Noah Manneschmidt (psoplayer)
 * https://github.com/noahm/MobileTribunal
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

function htmlToDoc($html)
{
	$doc = new DOMDocument();
	// gag error reporting for all the nonsense that bad html pages generate during parsing
	$orig = error_reporting(0);
	$doc->loadHTML($html);
	error_reporting($orig);
	return $doc;
}

function tribParseHTML($html)
{
	$doc = htmlToDoc($html);
	if (checkRecess($doc)) {
		return array('case' => 'recess', 'numGames' => 0);
	}
	return array( 'numGames' => getNumGames($html), 'case' => getCaseNo($doc), 'votesAllowed' => getVotesAllowed($doc), 'votesToday' => getVotesToday($doc));
}

function tribParseStartErrors($html)
{
	$doc = htmlToDoc($html);
	if ($doc->getElementById('guidelines')) {
		return true; // no problems here
	}
	if (checkUnderleveled($doc)) {
		return array('case' => 'underlevel');
	}
	if (checkRecess($doc)) {
		return array('case' => 'recess');
	}
	return false;
}

function checkUnderleveled($doc)
{
	$el = $doc->getElementById('finished_info_text');
	if ($el) {
		return !stripos($el->textContent, 'You have not reached the minimum level requirements')===false;
	} else {
		return false;
	}
}

function checkRecess($doc)
{
	$el = $doc->getElementById('finished_info_title');
	if ($el) {
		return !stripos($el->textContent, 'Tribunal in Recess')===false;
	} else {
		return false;
	}
}

function getCaseNo($doc)
{
	$xpath = new DOMXpath($doc);
	$caseno = $xpath->query("//*/span[@class='raw-case-number']");
	if ($caseno->length > 0) {
		return (int) $caseno->item(0)->textContent;
	} else {
		return 0;
	}
}

function getVotesToday($doc)
{
	$xpath = new DOMXpath($doc);
	$votes = $xpath->query("//*/span[@class='votes-today']");
	if ($votes->length > 0) {
		return (int) $votes->item(0)->textContent;
	} else {
		return 0;
	}
}

function getVotesAllowed($doc)
{
	$xpath = new DOMXpath($doc);
	$votes = $xpath->query("//*/span[@class='votes-allowed']");
	if ($votes->length > 0) {
		return (int) $votes->item(0)->textContent;
	} else {
		return 0;
	}
}

// not as simple as it used to be
// now it's packed in a javascript object literal output in an anonymous script tag
// DOM parsing won't help so much here
function getNumGames($html)
{
	// first find the variable declaration
	$chopped = substr($html, strpos($html, 'var caseData'));
	// then find the next semicolon
	$chopped = substr($chopped, 0, strpos($chopped, ';')+1);
	// parse for the game_count expression
	$matches = array();
	if (preg_match("/['\"]game_count['\"]:\s+(\d+),/", $chopped, $matches)) {
		return (int)$matches[1];
	} else {
		return 0;
	}
}

function tribParseLocation($header, $realm)
{
	if ( stristr($header, "Location: http://$realm.leagueoflegends.com/tribunal/finished\r\n") ) {
		return "finished";
	} elseif ( stristr($header, "HTTP/1.1 200 OK") ) {
		return "case";
	} else {
		return false;
	}
}

function parseRecaptcha($html)
{
	$pattern = "/challenge : '([\w-]+)',/";
	preg_match($pattern, $html, $matches);
	return $matches[1];
}

function parseLogin($html)
{
	//We expect a JSON response
	$result = json_decode($html, true);
	
	if( $result["success"] === false )
	{
		if( stristr($result["error"], "reCaptcha") )
			return "recaptcha";
		elseif( stristr($result["error"], "Authorized") )
			return "userpass";
		else
			return false;
	}
	elseif ($result["success"] == true )
		return "ok";
	else
		return false;

}
		
	