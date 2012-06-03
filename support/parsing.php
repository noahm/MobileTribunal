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

function tribParseHTML($html)
{
	// grab the element from the html
	$doc = new DOMDocument();

	// gag error reporting for all the nonsense that bad html pages generate during parsing
	$orig = error_reporting(0);
	$doc->loadHTML($html);
	error_reporting($orig);

	return array( 'numGames' => getNumGames($doc), 'case' => getCaseNo($doc) );
}


function getCaseNo($doc)
{
	$gamecount = $doc->getElementById('h_caseid');
	return (int) $gamecount->textContent;
}

function getNumGames($doc)
{
	$gamecount = $doc->getElementById('h_gamecount');
	return (int) $gamecount->textContent;
}

function tribParseLocation($header, $realm)
{

	if ( stristr($header, "Location: http://$realm.leagueoflegends.com/tribunal/finished\r\n") )
		return "finished";
	elseif ( stristr($header, "HTTP/1.0 200 OK") )
		return "case";
	else
		return false;

}