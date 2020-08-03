<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Formatter_ArrayBuilder
{
    public function getData($string)
    {
        $matches = WikiParser_PluginMatcher::match($string);
        $parser = new WikiParser_PluginArgumentParser;

        $data = [];

        foreach ($matches as $m) {
            $name = $m->getName();
            $arguments = $m->getArguments();

            if (isset($data[$name])) {
                if (! is_int(key($data[$name]))) {
                    $data[$name] = [$data[$name]];
                }

                $data[$name][] = $parser->parse($arguments);
            } else {
                $data[$name] = $parser->parse($arguments);
            }
        }

        return $data;
    }
}
