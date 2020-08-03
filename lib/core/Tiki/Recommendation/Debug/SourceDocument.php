<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Recommendation\Debug;

use Tiki\Recommendation\EngineOutput;

class SourceDocument implements EngineOutput
{
    private $type;
    private $object;
    private $title;

    public function __construct($type, $object, $title = null)
    {
        $this->type = $type;
        $this->object = $object;
        $this->title = $title;
    }

    public function __toString()
    {
        return tr('Source: %0:%1 (%2)', $this->type, $this->object, $this->title ?: tr('Unknown'));
    }
}
