<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Formatter_ValueFormatter_Reference extends Search_Formatter_ValueFormatter_Abstract
{
    private $separator = ', ';
    private $type = 'wiki page';

    public function __construct($arguments)
    {
        if (isset($arguments['separator'])) {
            $this->separator = $arguments['separator'];
        }

        if (isset($arguments['type'])) {
            $this->type = $arguments['type'];
        }
    }

    public function render($name, $value, array $entry)
    {
        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_function_object_link');

        foreach ((array) $value as $id) {
            $params = [
                'type' => $this->type,
                'id' => $id,
            ];
            $links[] = smarty_function_object_link($params, $smarty->getEmptyInternalTemplate());
        }

        return '~np~' . implode($this->separator, $links) . '~/np~';
    }
}
