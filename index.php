<?php
/**
 * Created by PhpStorm.
 * User: Nataly_Ivan
 * Date: 19.10.2017
 * Time: 22:03
 */

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Cookie\SetCookie as CookieParser;
use PHPHtmlParser\Dom;

$client = new Client(['cookies' => true]);

$baseUrl = 'https://account.kyivstar.ua/';

// first
$response = $client->get("{$baseUrl}cas/login",
	[
		'allow_redirects' => false,
		'headers' => [
			'User-Agent' => 'Tegos/1.1'
		]
	]
);

$dom = new Dom;
$dom->load($response->getBody());

$form = $dom->find('#auth-form');
$execution = $form->find('input[name=execution]')->getAttribute('value');
$lt = $form->find('input[name=lt]')->getAttribute('value');
$_eventId = $form->find('input[name=_eventId]')->getAttribute('value');
$rememberMe = 'true';
$password = 'G+DWkmvC!';
$username = '+380676222404';

var_dump($_eventId);
var_dump($execution);
var_dump($lt);

/** @var GuzzleHttp\Cookie\CookieJar $cookieJar */
$cookieJar = $client->getConfig('cookies');
$JSESSIONID = $cookieJar->getCookieByName('JSESSIONID')->getValue();


//$cookie = $cookieParser->fromString("Set-Cookie: {$entryCookie}");
//var_dump($JSESSIONID);
//echo $response->getBody();
//echo $response->getBody();
//exit('da');

// second - get Token
//https://account.kyivstar.ua/cas/auth/authSupport.rpc
/** @var GuzzleHttp\Cookie\CookieJar $cookieJar */
$cookieJar = $client->getConfig('cookies');


$response = $client->get("{$baseUrl}cas/auth/auth.nocache.js;jsessionid={$JSESSIONID}",
	[
		'headers' => [
			'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36'
		]
	]
);

$cookieJar = $client->getConfig('cookies');

//$client = new Client();
try {
	$response = $client->post("{$baseUrl}cas/auth/authSupport.rpc",
		[
			'timeout' => 5.0,
			'headers' => [
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
				'Content-Type' => 'text/x-gwt-rpc; charset=UTF-8',
				'Referer' => 'https://account.kyivstar.ua/cas/login',
				'Origin' => 'https://account.kyivstar.ua',
				'Connection' => 'keep-alive',
				'X-GWT-Module-Base' => 'https://account.kyivstar.ua/cas/auth/',
				'X-GWT-Permutation' => 'C84A3FFE6146268C5294890C7ADED1FE',
				'Accept' => '*/*',
				'Accept-Encoding' => 'gzip, deflate, br',
				'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,uk;q=0.2',
			]
		]
	);

} catch (RequestException $e) {
	echo Psr7\str($e->getRequest());
	if ($e->hasResponse()) {
		echo Psr7\str($e->getResponse());
	}
}
exit('da');

$response = $client->post("{$baseUrl}cas/login", [
	'allow_redirects' => false,
	'form_params' => [
		'execution' => 'e8s1',
		'lt' => 'LT-2563707-HbvcHaBuMe5e9zKCpEEStEGlRM2Pdb-s1n1',
		'_eventId' => 'submit',
		'password' => 'G+DWkmvC!',
		'username' => '+380676222404',
		'rememberMe' => 'true',
		'token' => 'AT-537422-5bdJ0us5gdexfeixLiQM9wqdcbs30p-s1n1',
		'authenticationType' => 'MSISDN_PASSWORD'
	]
]);

$location = $response->getHeader('Location');

/** @var GuzzleHttp\Cookie\CookieJar $cookieJar */
$cookieJar = $client->getConfig('cookies');
$JSESSIONID = $cookieJar->getCookieByName('JSESSIONID')->getValue();
$JSESSIONID = 'LJNyZyJW5WGvnK1Cvy5GK0t0bGgN1MQYp87s9LnPjbBnLLBtm7cC!-177193515';

$cookiesText = $response->getHeader('set-cookie');

var_dump($location);
//var_dump($cookiesText);
//var_dump($JSESSIONID);

exit('da');
$client = new Client();

$baseUrl = 'https://b2b.kyivstar.ua/';

$response = $client->get("{$baseUrl}tbmb/disclaimer/show.do",
	[
		'allow_redirects' => false,
		'headers' => [
			'User-Agent' => 'Tegos/1.1',
			'Cookie' => "JSESSIONID={$JSESSIONID};"
		]
	]
);


$result = $response->getBody();
$result = iconv('windows-1251', 'utf-8', $result);
echo $result;