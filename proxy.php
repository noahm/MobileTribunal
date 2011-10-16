<?php
function tribInit($name, $pass, $realm, $ch)
{

	//Login Page

	$url = "https://$realm.leagueoflegends.com/user/login";
	$data = array ('name' => $name, 'pass' => $pass, 'form_id' => "user_login");
	$data = http_build_query($data);

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);	//Eventually needs to be changed to check for Riot certificate

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
		$caseno = $matches[1];
	else
		return false;

	return array("cookies" => $cookies, "caseno" => $caseno);

}

function tribGetCase($caseno, $realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/tribunal/case/$caseno/review";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	if ( $result === false )
		return false;
	else
		return array("html" => $result["html"], "cookies" => $result["cookies"]);

}

function tribGetGame($caseno, $gameno, $realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/case/$caseno/get-game/$gameno";
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

function tribSkipCase($caseno, $realm, $ch, $cookies)
{

	$url = "http://$realm.leagueoflegends.com/tribunal/cases/skip/$caseno";
	$result = getHtmlHeaderandCookies($ch, $url, $cookies);
	$pattern = "/Location: http:\/\/$realm\.leagueoflegends\.com\/tribunal\/case\/([0-9]*)\/review\r\n/isU";
	if ( $result === false )
		return false;
	elseif ( preg_match($pattern, $result["header"], $matches) != 0 )
		$caseno = $matches[1];
	else
		return false;

	return array("caseno" => $caseno, "cookies" => $result["cookies"]);

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
