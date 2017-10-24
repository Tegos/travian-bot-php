<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Cookie\SetCookie as CookieParser;
use GuzzleHttp\TransferStats;
use PHPHtmlParser\Dom;

class Game
{

	public $client;

	protected $villageId = 3202;
	protected $ajaxToken = '';

	protected $baseUrl = 'https://ts80.travian.com';

	public function __construct()
	{
		$this->client = new Client(
			[
				'cookies' => true,
				'base_uri' => $this->baseUrl,
				'verify' => false
			]
		);
	}


	public function makeAuth()
	{
		try {
			$response = $this->client->post('/dorf1.php',
				[
					'timeout' => 5.0,
					'headers' => [
						'User-Agent' => Config::get('user_agent'),
						'Accept' => '*/*',
						'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,uk;q=0.2',
					],
					'form_params' => [
						'name' => Config::get('travian_login'),
						'password' => Config::get('travian_password'),
						's1' => 'submit',
						'login' => time()
					]
				]
			);
		} catch (\Exception $e) {
			return false;
		}

		// set ajaxToken
		$dom = new Dom;
		$dom->setOptions([
			'removeScripts' => false,
		]);
		$dom->load($response->getBody());

		$script = $dom->find('script')[0]->text();
		$script = str_replace('window.', '', $script);
		$vars = explode(';', trim($script));

		foreach ($vars as $var) {
			$exp = explode('=', $var);
			if (trim($exp[0]) === 'ajaxToken') {
				$this->ajaxToken = str_replace("'", '', trim($exp[1]));
			}
		}

		//echo $response->getBody();
		return true;
	}

	protected function makeRequest($requestData, $debug = false)
	{
		sleep(rand(1, 5));

		$option = [
			'timeout' => 5.0,
			'headers' =>
				[
					'User-Agent' => Config::get('user_agent'),
					'Origin' => 'https://ts80.travian.com'
				],

		];

		if ($debug) {
			$option['on_stats'] = function (TransferStats $stats) {
				echo Psr7\str($stats->getRequest());
				echo "\n";
			};
		}


		if (isset($requestData['body'])) {
			$option['form_params'] = $requestData['body'];
		}

		$response = $this->client->request(
			$requestData['method'],
			$requestData['url'],
			$option
		);
	}

	public function prepareFarmList()
	{
		try {
			//$this->setActiveVillageRequest();

			$rallyPointFarmList = $this->client->get('/build.php?tt=99&id=39',
				[
					'timeout' => 5.0,
					'headers' => [
						'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
						'Accept' => '*/*',
						'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,uk;q=0.2',
					]
				]
			);

			$dom = new Dom;
			$dom->load($rallyPointFarmList->getBody());

			$raidList = $dom->find('#raidList .listEntry');

			$raidArray = [];

			foreach ($raidList as $list) {
				$inputs = $list->find('input');

				$inputArray = [];

				foreach ($inputs as $input) {
					if ($input->getAttribute('name') && $input->getAttribute('value')) {
						$inputArray[$input->getAttribute('name')] = $input->getAttribute('value');
					}
				}


				$slotRow = $this->client->post('/ajax.php?cmd=raidListSlots',
					[
						'timeout' => 5.0,
						'headers' => [
							'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
							'Accept' => '*/*',
							'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,uk;q=0.2',
						],
						'form_params' => [
							'cmd' => 'raidListSlots',
							'lid' => $inputArray['lid'],
							'ajaxToken' => $this->ajaxToken
						]
					]
				);

				$resultJson = json_decode($slotRow->getBody()->getContents(), true);
				$detailSlots = $resultJson['response']['data']['list']['slots'];

				$slotArray = [];

				foreach ($detailSlots as $idSlot => $slot) {
					$slotArray["slot[{$idSlot}]"] = 'on';
				}

				$raidArray[] = array_merge($inputArray, $slotArray);
			}

			return $raidArray;


		} catch (\Exception $e) {
			echo $e->getMessage();
			return false;
		}
	}

	protected function getFarmListParamA()
	{
		$rallyPointFarmList = $this->client->get('/build.php?tt=99&id=39',
			[
				'timeout' => 5.0,
				'headers' => [
					'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
					'Accept' => '*/*',
					'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,uk;q=0.2',
				]
			]
		);

		$dom = new Dom;
		$dom->load($rallyPointFarmList->getBody());

		$raidList = $dom->find('#raidList .listEntry')[0];
		$aParam = $raidList->find('input[name=a]')->getAttribute('value');
		return $aParam;
	}

	public function runFarmList(array $raidArray)
	{
		try {
			$kRuns = 0;
			foreach ($raidArray as $raidData) {

				if ($kRuns > 0) {
					$aParam = $this->getFarmListParamA();
					$raidData['a'] = $aParam;
				}
				//var_dump($raidData);
				$this->makeRequest([
					'method' => 'POST',
					'url' => '/build.php?gid=16&tt=99',
					'body' => $raidData
				], true);
				$kRuns++;
			}
		} catch (\Exception $e) {
			echo $e->getMessage();
			return false;
		}
		return $kRuns;
	}


	protected function setActiveVillageRequest()
	{
		$this->makeRequest([
			'method' => 'GET',
			'url' => "/dorf1.php?newdid={$this->villageId}&"
		]);
	}
}