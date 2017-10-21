<?php
/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 19.10.2017
 * Time: 22:03
 */

require 'vendor/autoload.php';
include(__DIR__ . '/classes/autoloader.php');

new AutoLoader;


$game = new Game();

$auth = $game->makeAuth();

sleep(3);
if ($auth) {
	$task = file_get_contents('task.json');

	$raidArray = $game->prepareFarmList();
	if (count($raidArray)) {
		$raids = $game->runFarmList($raidArray);
		var_dump("{$raids} farm lists started.");
	}
}