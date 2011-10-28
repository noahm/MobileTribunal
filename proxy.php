<?php
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


	//Get the first case number
	$url = "http://$realm.leagueoflegends.com/tribunal/cases/review";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false )
		return false;
	else
		$cookies = $result["cookies"];

	$pattern = "/Location: http:\/\/$realm\.leagueoflegends\.com\/tribunal\/case\/([0-9]*)\/review\r\n/isU";
	if ( preg_match($pattern, $result["header"], $matches) != 0 )
		$case = $matches[1];
	else
		return false;

	return array("cookies" => $cookies, "case" => $case);

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
	$pattern = "/Location: http:\/\/$realm\.leagueoflegends\.com\/tribunal\/case\/([0-9]*)\/review\r\n/isU";
	if ( $result === false )
		return false;
	elseif ( preg_match($pattern, $result["header"], $matches) != 0 )
		$case = $matches[1];
	else
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
	$pattern = "/Location: http:\/\/$realm\.leagueoflegends\.com\/tribunal\/case\/([0-9]*)\/review\r\n/isU";
	if ( $result === false )
		return false;
	elseif ( preg_match($pattern, $result["header"], $matches) != 0 )
		$case = $matches[1];
	else
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

	curl_setopt($ch, CURLOPT_USERAGENT, 'TribunalMobile/0.1 (https://tribunal.phpfogapp.com/)');
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
