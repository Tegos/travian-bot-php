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

$log = new \PHPLogger(__DIR__ . '/data/logs');
$tag = "TRAVIAN - CRON";

$log->i($tag, '---------------------');
$log->i($tag, "Cron start");

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


	$rand = (float)rand() / (float)getrandmax();
	$randRemoveMessage = (float)rand() / (float)getrandmax();

	$probability += $rand;

	$log->i($tag, 'Probability: ' . $probability);

	if ($probability < 0.4) {
		throw new \Exception('Random break');
	}

	$runs = Helper::getTotalRuns();
	$runs++;

	if ($runs > 7) {
		Helper::setTotalRuns(0);
		throw new \Exception('Force break');
	}

	// random sleep
	sleep(rand(5, 200));

	$game = new Game();

	$auth = $game->makeAuth();

	if ($auth) {

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
	}

} catch (Exception $e) {
	$log->e($tag, $e->getMessage());
}

$log->i($tag, 'Execute time: ' . round(microtime(true) - $start, 4) . ' sec.');