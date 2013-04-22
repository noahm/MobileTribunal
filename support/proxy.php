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
function tribLogin($name, $pass, $realm, $recaptcha_challenge, $recaptcha_response, $ch)
{

	//Submit Riot's login page
	$url = "https://$realm.leagueoflegends.com/user/login";
	$data = array ('name' => $name, 'pass' => $pass, 'form_id' => "user_login",
			'recaptcha_challenge_field' => $recaptcha_challenge,
			'recaptcha_response_field' => $recaptcha_response,);
	$data = http_build_query($data);

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/assets/certificates/cacert.crt");

	$result = getHtmlHeaderAndCookies($ch, $url, array());
	if ( $result === false )
		return false;

	curl_setopt($ch, CURLOPT_POST, false);

	//Check where we end up - redirects to the login page mean bad pass/recaptcha
	if( stristr($result["header"], "Location: https://$realm.leagueoflegends.com/user/login\r\n") ) //Bad recaptcha responses redirect to login
		return array('status' => 'recaptcha');

	elseif( stristr($result["header"], "200 OK") ) //bad user/pw's just goes through
		return array('status' => 'userpass');

	elseif( stristr($result["header"], "Location: http://$realm.leagueoflegends.com\r\n") ) //success redirects to main page
		return array('status' => 'success', 'cookies' => $result['cookies']);

	else
		return false;
}

function tribInit($realm, $cookies, $ch)
{

	//Successful login, now get to the tribunal
	$url = "http://$realm.leagueoflegends.com/tribunal/en/guidelines/";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false ) {
		return false;
	} else {
		$cookies = $result["cookies"];
	}

	// check for recess or not matching summoner lvl requirements
	$r = tribParseStartErrors($result['html']);

	if ($r === false)
		return false;
	elseif ($r != true)
		return array("cookies" => $cookies, "case" => $r["case"]); // case => [underlevel, recess]

	//Submit "Agree" Page
	$url = "http://$realm.leagueoflegends.com/tribunal/en/accept/";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false ) {
		return false;
	}

	//Get the first case number
	return tribGetCase($realm, $ch, $result["cookies"]);

}

function tribGetRecaptcha($ch, $realm)
{
	$url = "https://www.google.com/recaptcha/api/challenge?k=6Ldhvd0SAAAAAHwXC1e_b3N_RuA7WmusxCqFnFyu";
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/assets/certificates/cacert.crt");
	$result = getHtmlHeaderandCookies($ch, $url, "");
	if ( $result === false )
		return false;
	else {
		$challenge = parseRecaptcha($result["html"]);
		$url = "https://www.google.com/recaptcha/api/image?c=" . $challenge;
		curl_setopt($ch, CURLOPT_REFERER, "https://$realm.leagueoflegends.com/user/login");
		$result = getHtmlHeaderAndCookies($ch, $url, "");

		if ( $result === false )
			return false;
		else
			return array('challenge' => $challenge, 'image' => "data:image/png;base64," . base64_encode($result["html"]));
	}
}

function tribGetCase($realm, $ch, $cookies)
{
	$url = "http://$realm.leagueoflegends.com/tribunal/en/review/";
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

	$url = "http://$realm.leagueoflegends.com/tribunal/en/vote/$case/";
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

	$url = "http://$realm.leagueoflegends.com/tribunal/en/vote/$case/";
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
	//Use a real browser's useragent so cloudflare doesn't think we're a spammer
	$useragent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:19.0) Gecko/20100101 Firefox/19.0';
	$useragent .= ' MobileTribunal/2.2 (https://github.com/noahm/MobileTribunal/)';
	curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);	

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
