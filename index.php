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
$log->i($tag, "Action - Farm");

try {
	$timeLondon = new \DateTimeZone('Europe/London');
	$date = new \DateTime();
	$date->setTimezone($timeLondon);
	$hours = (int)$date->format('H');
	$probability = 0.0;


	if ($hours > 0 && $hours < 5) {
		$probability -= 0.1;
	}

	$log->i($tag, 'Game server time: ' . $date->format('d.m.Y H:i:s'));

	// auth
	$game = new Game();
	$auth = $game->makeAuth();

	$rand = (float)rand() / (float)getrandmax();
	$randRemoveMessage = (float)rand() / (float)getrandmax();

	$little_rand = (rand(5, 30) / 100);
	$probability += ($rand + $little_rand);

	$log->i($tag, 'Probability: ' . $probability);

	if ($probability < 0.1) {
		throw new \Exception('Random break');
	}

	$runs = Helper::getTotalRuns();


	if ($runs > rand(5,9)) {
		Helper::setTotalRuns(0);
		throw new \Exception('Force break');
	}

	// random sleep
	Helper::randomSleep(5, 150);

	if ($auth) {
		//$game->makeRandomActions();
		$raidArray = $game->prepareFarmList();
		if (count($raidArray)) {
			$allowed = implode(',', Helper::getAllowedFarmList());
			$raids = $game->runFarmList($raidArray);
			$log->i($tag, "{$raids} farm lists started.");

			$log->i($tag, "Allowed {$allowed}.");
		}

		$runs++;
		Helper::setTotalRuns($runs);
	}

} catch (Exception $e) {
	$log->e($tag, $e->getMessage());
}

$log->i($tag, 'Execute time: ' . round(microtime(true) - $start, 4) . ' sec.');