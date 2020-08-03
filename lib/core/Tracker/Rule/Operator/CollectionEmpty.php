<?php

namespace Tiki\Lib\core\Tracker\Rule\Operator;

use Tiki\Lib\core\Tracker\Rule\Type\Nothing;

class CollectionEmpty extends Operator
{
    public function __construct()
    {
        parent::__construct(tr('is empty'), Nothing::class, '.val().length===0');
    }
}
