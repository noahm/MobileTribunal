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

require_once 'parsing.php';
function tribInit($name, $pass, $realm, $ch)
{

	//Login Page

	$url = "https://$realm.leagueoflegends.com/user/login";
	$data = array ('name' => $name, 'pass' => $pass, 'form_id' => "user_login");
	$data = http_build_query($data);

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/assets/certificates/cacert.crt");

	$result = getHtmlHeaderAndCookies($ch, $url, array());
	if ( $result === false ) {
		return false;
	} else {
		$cookies = $result["cookies"];
	}

	curl_setopt($ch, CURLOPT_POST, false);

	//"Get Started" Page
	$url = "http://$realm.leagueoflegends.com/tribunal/";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false ) {
		return false;
	} else {
		$cookies = $result["cookies"];
	}

	// check for recess or not matching summoner lvl requirements
	if ($r = tribParseStartErrors($result['html'])) {
		return $r; // case => [underlevel, recess, unknown]
	}

	//"Agree" Page
	$url = "http://$realm.leagueoflegends.com/tribunal/en/guidelines/";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false ) {
		return false;
	} else {
		$cookies = $result["cookies"];
	}

	//Submit "Agree" Page
	$url = "http://$realm.leagueoflegends.com/tribunal/accept/";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false ) {
		return false;
	}

	//Get the first case number
	return tribGetCase($realm, $ch, $result["cookies"]);

}

function tribGetCase($realm, $ch, $cookies)
{
	$url = "http://$realm.leagueoflegends.com/tribunal/en/";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false ) {
		return false;
	} else {
		$loc = tribParseLocation($result["header"], $realm);
		if ( $loc === false ) {
			return false;
		} elseif ( $loc == "finished" ) {
			return array("cookies" => $result["cookies"], "case" => "finished");
		} elseif ( $loc == "case" ) {
			$caseInfo = tribParseHTML($result['html']);
			return array("cookies" => $result["cookies"], "case" => $caseInfo["case"], "numGames" => $caseInfo["numGames"]);
		}
	}
}

function tribGetGame($case, $game, $realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/tribunal/get_game/$case/$game/";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false ) {
		return false;
	} else {
		return array("JSON" => $result["html"], "cookies" => $result["cookies"]);
	}

}

function tribGetCaptcha($realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/tribunal/en/refresh_captcha/";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false ) {
		return false;
	} else {
		return array("captcha" => $result["html"], "cookies" => $result["cookies"]);
	}

}

function tribSkipCase($case, $realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/tribunal/vote/$case/";
	$data = array("decision"=>"skip");
	$data = http_build_query($data);

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);

	if ( $result === false ) {
		return false;
	}

	curl_setopt($ch, CURLOPT_POST, false);

	return tribGetCase($realm, $ch, $result["cookies"]);

}

function tribReviewCase($case, $punish, $captcha, $realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/tribunal/vote/$case/";
	$data = array("decision" => $punish ? "punish" : "pardon");
	$data = http_build_query($data);

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);

	if ( $result === false ) {
		return false;
	}

	curl_setopt($ch, CURLOPT_POST, false);

	return tribGetCase($realm, $ch, $result["cookies"]);

}

function tribCheckCaptcha($captcha, $realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/tribunal/en/captcha_check/$captcha/";

	$result = getHtmlHeaderandCookies($ch, $url, $cookies);

	if ( $result === false ) {
		return false;
	}

	return array("captchaResult" => $result["html"], "cookies" => $result["cookies"]);

}

function getHtmlHeaderAndCookies($ch, $url, $cookies)
{

	curl_setopt($ch, CURLOPT_USERAGENT, 'MobileTribunal/1.0 (https://github.com/noahm/MobileTribunal/)');
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	//Reassemble cookies
	if ( !empty($cookies) )
	{
		foreach( $cookies as $name => $value ) {
			$cookieStrings[] = "$name=$value";
		}
		curl_setopt($ch, CURLOPT_COOKIE, implode("; ", $cookieStrings));
	}

	$result = curl_exec($ch);

	if ( $result === false ) {
		return false;
	}

	//Parse cookies
	$pattern = "/Set-Cookie: (.*);/U";
	if ( preg_match_all($pattern, $result, $matches) != 0 )
	{
		foreach ( $matches[1] as $match )
		{
			$newCookie = explode("=", $match);
			$newCookies[$newCookie[0]] = $newCookie[1];
		}
		
		$cookies = array_merge($cookies, $newCookies);
	}

	//Parse content
	$contentpos = strpos($result, "\r\n\r\n")+4;
	$html = substr($result, $contentpos);

	//Parse header
	$header = substr($result, 0, $contentpos);

	return array("html" => $html, "header" => $header, "cookies" => $cookies);

}
