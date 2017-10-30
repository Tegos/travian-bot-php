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
}