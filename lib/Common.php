<?php

class Common
{	
	public static function ifSetOr(&$var, $or)
	{
		return isset($var) ? $var : $or;
	}

	public static function ifKeyOr($key, &$array, $or)
	{
		return array_key_exists($key, $array) ? $array[$key] : $or;
	}	
}
?>