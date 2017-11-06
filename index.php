<?php
/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 19.10.2017
 * Time: 22:03
 */

$start = microtime(true);
require 'vendor/autoload.php';
include(__DIR__ . '/classes/autoloader.php');

new \AutoLoader;

$action = 'default';
if (isset($_GET['action'])) {
	$action = $_GET['action'];
} else {
	parse_str($argv[1], $params);
	if (isset($params['action'])) {
		$action = $params['action'];
	}
}


$log = new \PHPLogger(__DIR__ . '/data/logs');
$tag = "TRAVIAN - CRON";

$log->i($tag, '---------------------');
$log->i($tag, "Cron start");
$log->i($tag, "Action - {$action}");

try {
	$timeLondon = new \DateTimeZone('Europe/London');
	$date = new \DateTime();
	$date->setTimezone($timeLondon);
	$hours = (int)$date->format('H');
	$probability = 0.0;


	if ($hours < 12 || $hours > 22) {
		$probability += 0.2;
	}

	$log->i($tag, 'Game server time: ' . $date->format('d.m.Y H:i:s'));

	// auth
	$game = new Game();
	$auth = $game->makeAuth();

	$rand = (float)rand() / (float)getrandmax();
	$randRemoveMessage = (float)rand() / (float)getrandmax();

	$probability += $rand;

	$log->i($tag, 'Probability: ' . $probability);

	// for another action
	if ($action !== 'default') {
		$probability += 0.35;
	}

	if ($probability < 0.5) {
		throw new \Exception('Random break');
	}

	$runs = Helper::getTotalRuns();
	$runs++;

	if ($runs > 7) {
		Helper::setTotalRuns(0);
		throw new \Exception('Force break');
	}

	// random sleep
	//sleep(rand(5, 150));

	if ($auth) {
		switch ($action) {
			case 'default':
				if ($randRemoveMessage > 0.5) {
					$totalMessages = $game->clearReport();
					$log->i($tag, 'Messages : ' . $totalMessages . ' removed');
				}

				$raidArray = $game->prepareFarmList();
				if (count($raidArray)) {
					$raids = $game->runFarmList($raidArray);
					$log->i($tag, "{$raids} farm lists started.");
					var_dump("{$raids} farm lists started.");
				}

				Helper::setTotalRuns($runs);
				break;

			case 'cage':
				$game->makeBidForCages();
				break;
		}
	}

} catch (Exception $e) {
	$log->e($tag, $e->getMessage());
}

$log->i($tag, 'Execute time: ' . round(microtime(true) - $start, 4) . ' sec.');