<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Cookie\SetCookie as CookieParser;
use GuzzleHttp\TransferStats;
use PHPHtmlParser\Dom;
use GuzzleHttp\Cookie\FileCookieJar;

class Game
{

	private $client;

	protected $villageId = 3202;
	protected $ajaxToken = '';
	protected $player_uuid = '';

	protected $baseUrl = 'https://ts80.travian.com';

	private $cookieFile;

	public function __construct()
	{
		$this->cookieFile = HOME . '/data/cookie_jar.txt';

		$cookieJar = new FileCookieJar($this->cookieFile, true);

		$this->client = new Client(
			[
				'cookies' => $cookieJar,
				'base_uri' => $this->baseUrl,
				'verify' => false,
				'delay' => 3000
			]
		);
	}


	public function makeAuth()
	{
		set_time_limit(900); // 15min
		$result = true;
		$method = '';

		try {
			$method = 'GET';
			$response = $this->client->get('/dorf1.php',
				[
					'timeout' => 5.0,
					'headers' => [
						'User-Agent' => Config::get('user_agent'),
						'Accept' => '*/*',
						'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,uk;q=0.2',
					]
				]
			);

			$this->setAjaxToken($response);

		} catch (\Exception $e) {
			$result = false;
		}

		if (!$this->player_uuid || !$result) {
			try {
				$method = 'POST';
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

				$this->setAjaxToken($response);

				$result = true;
			} catch (\Exception $e) {
				$result = false;
			}
		}

		if ($this->player_uuid && $result) {
			$result = true;
		}

		var_dump($method);
		var_dump($this->player_uuid);
		var_dump($this->ajaxToken);

		//echo $response->getBody();
		return $result;
	}


	private function setAjaxTokenOld($response)
	{
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
			// player_uuid
			if (trim($exp[0]) === '_player_uuid') {
				$this->player_uuid = str_replace("'", '', trim($exp[1]));
			}
		}
	}

	private function setAjaxToken($response)
	{
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
			// player_uuid
			if (trim($exp[0]) === '_player_uuid') {
				$this->player_uuid = str_replace("'", '', trim($exp[1]));
			}
		}

		$text = $response->getBody();
		$data = explode("\n", $text);


		$length = count($data);
		for ($i = 0; $i < $length; $i++) {
			$line = $data[$i];
			$pos = strpos($line, 'Travian.beadiestWashoutWeedy');
			if ($pos !== false) {
				$token = trim($data[$i + 1]);
				$token = str_replace("'", '', $token);
				$token = str_replace('return', '', $token);
				$token = str_replace(';', '', $token);
				$token = trim($token);
				break;
			}
		}

		if (isset($token)) {
			if ($token) {
				$this->ajaxToken = $token;
			}
		}
		//die();
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
						'User-Agent' => Config::get('user_agent'),
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

				$param_sort = '';
				$param_direction = '';

				$rand = (float)rand() / (float)getrandmax();
				if ($rand > 0.3) {
					$param_sort = 'lastRaid';
					$param_direction = 'asc';
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
							'ajaxToken' => $this->ajaxToken,
							'sort' => $param_sort,
							'direction' => $param_direction
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

	protected function randomizeRaid(array $raidArray)
	{
		$countRaids = count($raidArray);

		if ($countRaids < 2) {
			return $raidArray;
		}

		$randItems = (int)($countRaids / 1.4);

		if ($randItems < 1) {
			$randItems = 1;
		}

		$keys = array_rand($raidArray, $randItems);

		if (!is_array($keys)) {
			$keys = [$keys];
		}

		foreach ($keys as $key) {
			unset($raidArray[$key]);
		}

		return $raidArray;
	}

	protected function getFarmListParamA()
	{
		$rallyPointFarmList = $this->client->get('/build.php?tt=99&id=39',
			[
				'timeout' => 5.0,
				'headers' => [
					'User-Agent' => Config::get('user_agent'),
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

			// todo need check
			$rand = (float)rand() / (float)getrandmax();
			if ($rand > 0.3) {
				shuffle($raidArray);
			}


			$allowed = Helper::getAllowedFarmList();
			foreach ($raidArray as $raidData) {
				if (!in_array($raidData['lid'], $allowed)) {
					continue;
				}
				if ($kRuns > 0) {
					$aParam = $this->getFarmListParamA();
					$raidData['a'] = $aParam;
				}
				//var_dump($raidData);
				$this->makeRequest([
					'method' => 'POST',
					'url' => '/build.php?gid=16&tt=99',
					'body' => $raidData
				]);
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

	public function clearReport()
	{
		$totalMessages = 0;
		// offensive - without losses
		$reportsPage = $this->client->get('/berichte.php?t=1&opt=AAABAA==',
			[
				'timeout' => 5.0,
				'headers' => [
					'User-Agent' => Config::get('user_agent'),
					'Accept' => '*/*',
					'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,uk;q=0.2',
				]
			]
		);

		$dom = new Dom;
		$dom->load($reportsPage->getBody());


		$inputs = $dom->find('div.reports table#overview td.sel input');

		$inputArray = [];

		foreach ($inputs as $input) {
			if ($input->getAttribute('name') && $input->getAttribute('value')) {
				$inputArray[$input->getAttribute('name')] = $input->getAttribute('value');
			}
		}

		$totalMessages += count($inputArray);

		$postData = [
			'page' => '1',
			'del' => 'Delete',
			's' => '1'
		];

		$postData = array_merge($postData, $inputArray);

		$this->makeRequest([
			'method' => 'POST',
			'url' => '/berichte.php?t=1',
			'body' => $postData
		], true);

		sleep(rand(1, 10));

		// merchants
		$reportsPage = $this->client->get('/berichte.php?t=4&opt=AAALAAwADQAOAA==',
			[
				'timeout' => 5.0,
				'headers' => [
					'User-Agent' => Config::get('user_agent'),
					'Accept' => '*/*',
					'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,uk;q=0.2',
				]
			]
		);

		$dom = new Dom;
		$dom->load($reportsPage->getBody());


		$inputs = $dom->find('div.reports table#overview td.sel input');

		$inputArray = [];

		foreach ($inputs as $input) {
			if ($input->getAttribute('name') && $input->getAttribute('value')) {
				$inputArray[$input->getAttribute('name')] = $input->getAttribute('value');
			}
		}

		$totalMessages += count($inputArray);

		$postData = [
			'page' => '1',
			'del' => 'Delete',
			's' => '1'
		];

		$postData = array_merge($postData, $inputArray);

		$this->makeRequest([
			'method' => 'POST',
			'url' => '/berichte.php?t=4',
			'body' => $postData
		], true);

		return $totalMessages;

	}

	protected function getAuctionParamZ()
	{
		$auction = $this->client->get('/hero.php?t=4&action=buy&filter=9',
			[
				'timeout' => 5.0,
				'headers' => [
					'User-Agent' => Config::get('user_agent'),
					'Accept' => '*/*',
					'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,uk;q=0.2',
				]
			]
		);

		$dom = new Dom;
		$dom->load($auction->getBody());

		$table = $dom->find('#auction table tbody')[0];

		$row = $table->find('tr')[0];

		$link = $row->find('td.bid a');
		$href = $link->getAttribute('href');
		$href = str_replace('hero.php?', '', $href);
		$href = html_entity_decode($href);

		parse_str($href, $getArray);
		$zParam = $getArray['z'];

		return $zParam;
	}

	public function makeBidForCages()
	{
		// cage list
		$cagePage = $this->client->get('/hero.php?t=4&action=buy&filter=9',
			[
				'timeout' => 5.0,
				'headers' => [
					'User-Agent' => Config::get('user_agent'),
					'Accept' => '*/*',
					'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,uk;q=0.2',
				]
			]
		);

		//echo $cagePage->getBody();

		$dom = new Dom;
		$dom->load($cagePage->getBody());
		//$dom->firstChild()

		$table = $dom->find('#auction table tbody')[0];

		$arrayBids = [];

		$rows = $table->find('tr');

		foreach ($rows as $row) {
			$description = trim($row->find('td.name')->text());
			$link = $row->find('td.bid a');
			$href = $link->getAttribute('href');
			$href = str_replace('hero.php?', '', $href);
			$href = html_entity_decode($href);

			parse_str($href, $getArray);

			$arrayBids[] = [
				'description' => Helper::cleanString($description),
				'bids' => (int)trim($row->find('td.bids')->text()),
				'silver' => (int)trim($row->find('td.silver')->text()),
				'bid' => trim($link->text()),
				'a' => trim($getArray['a']),
				'z' => trim($getArray['z'])
			];
		}

		foreach ($arrayBids as $k => $arrayBid) {
			$price = rand($arrayBid['silver'], $arrayBid['silver'] + $arrayBid['silver'] * 0.2);
			$arrayBids[$k]['price'] = $price;
		}

		$postData = [
			'page' => '1',
			'filter' => '9',
			'action' => 'buy'
		];

		var_dump($arrayBids);

		try {
			$kRuns = 0;
			foreach ($arrayBids as $arrayBid) {

				if ($arrayBid['bids'] < 1) {
					$zParam = $this->getAuctionParamZ();
					$arrayBid['z'] = $zParam;


					$postData = array_merge($postData,
						[
							'z' => $arrayBid['z'],
							'a' => $arrayBid['a'],
							'maxBid' => $arrayBid['price']
						]
					);

					$this->makeRequest([
						'method' => 'POST',
						'url' => '/hero.php?t=4',
						'body' => $postData
					]);
					$kRuns++;
				}
			}
		} catch (\Exception $e) {
			echo $e->getMessage();
			return false;
		}

		var_dump($kRuns);

		return $kRuns;
	}


	public function makeRandomActions()
	{
		try {
			$kRuns = 0;

			$actions = Helper::getGameAction();
			$count = count($actions);
			$min = 1;
			$max = 4;
			if ($max > $count) {
				$max = $count;
			}
			$num = rand($min, $max);

			$result = [];
			$randomAction = array_rand($actions, $num);
			if (!is_array($randomAction)) {
				$randomAction = [$randomAction];
			}
			foreach ($randomAction as $k) {
				$result[] = $actions[$k];
			}

			if (count($result)) {
				foreach ($result as $link) {
					$this->makeRequest([
						'method' => 'GET',
						'url' => $link
					]);
					$kRuns++;
				}
			}
		} catch (\Exception $e) {
			echo $e->getMessage();
			return false;
		}

		return $kRuns;
	}

}