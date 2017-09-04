<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 2017-09-04
 * Time: 9:53
 */

namespace GsJohn\Dysms;

use Illuminate\Support\Facades\Facade;

class DysmsFacade extends Facade
{
	public static function getFacadeAccessor()
	{
		return 'dysms';
	}
}