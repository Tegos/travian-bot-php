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
$log->i($tag, "Action - Cage");

try {
	$timeLondon = new \DateTimeZone('Europe/London');
	$date = new \DateTime();
	$date->setTimezone($timeLondon);

	$probability = 0.0;

	$log->i($tag, 'Game server time: ' . $date->format('d.m.Y H:i:s'));

	// auth
	$game = new Game();
	$auth = $game->makeAuth();

	$rand = (float)rand() / (float)getrandmax();

	$probability += $rand;

	$log->i($tag, 'Probability: ' . $probability);

	if ($probability < 0.2) {
		throw new \Exception('Random break');
	}

	// auth
	$game = new Game();
	$auth = $game->makeAuth();

	// random sleep
	sleep(rand(10, 100));

	if ($auth) {
		$bids = $game->makeBidForCages();
		$log->i($tag, "{$bids} bids.");
	}

} catch (Exception $e) {
	$log->e($tag, $e->getMessage());
}

$log->i($tag, 'Execute time: ' . round(microtime(true) - $start, 4) . ' sec.');