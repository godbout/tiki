<?php


namespace Tiki\Lib\core\Tracker\Rule\Action;


use Tiki\Lib\core\Tracker\Rule\Type\Text;

class Required extends Action
{
	public function __construct()
	{
		parent::__construct(tr('Required'), Text::class, '.rules("add",{required:true,messages:{required:"%argument%"}})');
	}
}
