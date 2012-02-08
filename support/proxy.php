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

	$result = getHtmlHeaderAndCookies($ch, $url, "");
	if ( $result === false )
		return false;
	else
		$cookies = $result["cookies"];

	curl_setopt($ch, CURLOPT_POST, false);

	//"Get Started" Page
	$url = "http://$realm.leagueoflegends.com/Tribunal";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false )
		return false;
	else
		$cookies = $result["cookies"];

	//"Accept" Page
	$url = "http://$realm.leagueoflegends.com/tribunal/acceptance";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false )
		return false;
	else
		$cookies = $result["cookies"];

	if ( stristr($result["header"], "Location: http://$realm.leagueoflegends.com/tribunal/error/level\r\n") )
		return array("cookies" => $result["cookies"], "case" => "level");

	//Get the first case number
	$url = "http://$realm.leagueoflegends.com/tribunal/cases/review";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);

	if ( $result === false )
		return false;
	elseif ( !$case = tribParseLocation($result["header"], $_SESSION["realm"]) )
		return false;

	return array("cookies" => $result["cookies"], "case" => $case);

}

function tribGetCase($case, $realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/tribunal/case/$case/review";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false )
		return false;
	else
	{

		$caseInfo = tribParseHTML($result['html']);
		return array("numGames" => $caseInfo["numGames"], "formTokens" => json_encode($caseInfo["formTokens"]), "cookies" => $result["cookies"]);

	}

}

function tribGetGame($case, $game, $realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/case/$case/get-game/$game";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
 	if ( $result === false )
 		return false;
 	else
		return array("JSON" => $result["html"], "cookies" => $result["cookies"]);

}

function tribGetCaptcha($realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/cases/captcha";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false )
		return false;
	else
		return array("captcha" => $result["html"], "cookies" => $result["cookies"]);

}

function tribSkipCase($case, $realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/tribunal/cases/skip/$case";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);

	if ( $result === false )
		return false;
	elseif ( !$case = tribParseLocation($result["header"], $_SESSION["realm"]) )
		return false;

	return array("case" => $case, "cookies" => $result["cookies"]);

}

function tribReviewCase($case, $formTokens, $punish, $captcha, $realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/tribunal/case/$case/review";
	$data = array_merge($formTokens, array("op"=>$punish?"Punish":"Pardon", "captcha_result"=>$captcha));
	$data = http_build_query($data);

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	curl_setopt($ch, CURLOPT_POST, false);

	if ( $result === false )
		return false;
	elseif ( !$case = tribParseLocation($result["header"], $_SESSION["realm"]) )
		return false;

	return array("case" => $case, "cookies" => $result["cookies"]);

}

function tribCheckCaptcha($captcha, $realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/cases/captcha-check";
	$data = array("captcha"=>$captcha);
	$data = http_build_query($data);

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	curl_setopt($ch, CURLOPT_POST, false);

	if ( $result === false )
		return false;

	return array("captchaResult" => $result["html"], "cookies" => $result["cookies"]);

}

function getHtmlHeaderAndCookies($ch, $url, $cookies)
{

	curl_setopt($ch, CURLOPT_USERAGENT, 'MobileTribunal/0.9 (https://github.com/noahm/MobileTribunal/)');
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	
	if ( $cookies != "" )
		curl_setopt($ch, CURLOPT_COOKIE, $cookies);

	$result= curl_exec($ch);

	if ( $result === false )
		return false;

	//Parse cookies
	$pattern = "/Set-Cookie: (.*);/U";
	if ( preg_match_all($pattern, $result, $matches) != 0 )
		$cookies = implode("; ", $matches[1]);

	//Parse content
	$contentpos = strpos($result, "\r\n\r\n")+4;
	$html = substr($result, $contentpos);

	//Parse header
	$header = substr($result, 0, $contentpos);

	return array("html" => $html, "header" => $header, "cookies" => $cookies);

}
