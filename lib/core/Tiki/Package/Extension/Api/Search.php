<?php

namespace Tiki\Package\Extension\Api;

use Tiki\Package\Extension\Api;

class Search extends Api
{
	protected static $sources = [];

	public static function setSources($package, $sources)
	{
		foreach ($sources as $source) {
			try {
				self::$sources[] = new $source->class;
			} catch (\Exception $e) {
				error_log($e->getMessage());
			}
		}
	}

	public static function getSources()
	{
		return self::$sources;
	}
}
