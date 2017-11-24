<?php
/**
 * Created by PhpStorm.
 * User: Nataly_Ivan
 * Date: 30.10.2017
 * Time: 22:10
 */

final class Helper
{
	public static function getTotalRuns()
	{
		try {
			$file = __DIR__ . '/../data/data_runs';
			$content = (int)file_get_contents($file);

		} catch (\Exception $exception) {
			$content = '';
		}
		$runs = (int)$content;
		if ($runs < 1) {
			$runs = 1;
		}
		return $runs;
	}

	public static function setTotalRuns($runs = 1)
	{
		$file = __DIR__ . '/../data/data_runs';
		$fh = fopen($file, 'w');
		fwrite($fh, $runs);
		fclose($fh);
	}

	public static function getAllowedFarmList()
	{
		try {
			$file = __DIR__ . '/../data/allowed_farmList';
			$content = json_decode(file_get_contents($file), true);

		} catch (\Exception $exception) {
			$content = [];
		}
		$list = (array)$content;
		return $list;
	}

	public static function getGameAction()
	{
		try {
			$file = __DIR__ . '/../data/actions';
			$content = json_decode(file_get_contents($file), true);

		} catch (\Exception $exception) {
			$content = [];
		}
		$list = (array)$content;
		return $list;
	}

	public static function cleanString($string)
	{
		$s = trim($string);

		$s = str_replace('&#x202d;', '', $s);
		$s = str_replace('&times;', '', $s);
		$s = str_replace('&#x202c;', '', $s);

		$s = iconv("UTF-8", "UTF-8//IGNORE", $s); // drop all non utf-8 characters

		// this is some bad utf-8 byte sequence that makes mysql complain - control and formatting i think
		$s = preg_replace('/(?>[\x00-\x1F]|\xC2[\x80-\x9F]|\xE2[\x80-\x8F]{2}|\xE2\x80[\xA4-\xA8]|\xE2\x81[\x9F-\xAF])/', ' ', $s);

		$s = preg_replace('/\s+/', ' ', $s); // reduce all multiple whitespace to a single space

		return $s;
	}

	public static function randomSleep($min = 10, $max = 100)
	{
		var_dump($_SERVER['HTTP_HOST']);
		if ($_SERVER['HTTP_HOST'] != 'travian.loc') {
			sleep(rand($min, $max));
		} else {
			var_dump('No sleep');
		}

	}
}