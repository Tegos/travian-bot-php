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

$log = new \PHPLogger(__DIR__ . "/data/logs");
$tag = "TRAVIAN - CRON";

$log->i($tag, '---------------------');
$log->i($tag, "Cron start");

try {
	$timeLondon = new \DateTimeZone('Europe/London');
	$date = new \DateTime();
	$date->setTimezone($timeLondon);
	$hours = (int)$date->format('H');

	echo $hours;
	//echo $date->format('Y-m-d H:i:s');

	die();

	$rand = (float)rand() / (float)getrandmax();
	if ($rand < 0.2) {
		$execute = false;
	} else {
		$execute = true;
	}

	if (!$execute) {
		throw new \Exception('Random break');
	}

	// random sleep
	sleep(rand(3, 110));

	$game = new Game();

	$auth = $game->makeAuth();

	sleep(1);
	if ($auth) {
		$task = file_get_contents(__DIR__ . '/task.json');

		$raidArray = $game->prepareFarmList();
		if (count($raidArray)) {
			$raids = $game->runFarmList($raidArray);
			var_dump("{$raids} farm lists started.");
		}
	}

} catch (Exception $e) {
	$log->e($tag, $e->getMessage());
}

$log->i($tag, 'Execute time: ' . round(microtime(true) - $start, 4) . ' sec.');