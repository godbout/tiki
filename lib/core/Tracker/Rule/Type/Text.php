<?php

namespace Tiki\Lib\core\Tracker\Rule\Type;

use Tiki\Lib\core\Tracker\Rule\Action;
use Tiki\Lib\core\Tracker\Rule\Operator;

class Text extends Type
{
    public function __construct()
    {
        parent::__construct('text', [
            new Operator\TextEquals(),
            new Operator\TextContains(),
            new Operator\TextNotContains(),
            new Operator\TextIsEmpty(),
            new Operator\TextIsNotEmpty(),
            new Action\Required(),
        ]);
    }
}
