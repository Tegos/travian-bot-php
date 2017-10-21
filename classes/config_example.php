<?php
/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 20.10.2017
 * Time: 21:01
 */



final class  ConfigT
{

	private static $data =
		array(
			'travian_login' => 'Test',
			'travian_password' => 'test'
		);

	public static function get($key)
	{
		if (isset(self::$data[$key])) {
			return self::$data[$key];
		} else {
			return 0;
		}
	}


}