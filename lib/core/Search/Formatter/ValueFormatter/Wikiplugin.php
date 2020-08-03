<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Formatter_ValueFormatter_Wikiplugin extends Search_Formatter_ValueFormatter_Abstract
{
    private $arguments;

    public function __construct($arguments)
    {
        $this->arguments = $arguments;
    }

    public function render($name, $value, array $entry)
    {
        if (substr($name, 0, 11) !== 'wikiplugin_') {
            return $value;
        }
        $name = substr($name, 11);
        

        if (isset($this->arguments['content'])) {
            $content = $this->arguments['content'];

            // replace args in the plugin body with values from the results
            // look for %field_name% in the content and replace with $entry['field_name']
            if ($rc = preg_match_all('/%(\w+)%/', $content, $matches)) {
                for ($i = 0; $i < $rc; $i++) {
                    if (isset($entry[$matches[1][$i]])) {
                        $content = str_replace($matches[0][$i], $entry[$matches[1][$i]], $content);
                    }
                }
            }

            unset($this->arguments['content']);
        } else {
            $content = '';
        }
        $defaults = [];
        if (isset($this->arguments['default'])) {
            parse_str($this->arguments['default'], $defaults);
        }

        $params = [];
        foreach ($this->arguments as $key => $val) {
            if (isset($entry[$val])) {
                $params[$key] = $entry[$val];
            } elseif (isset($defaults[$key])) {
                $params[$key] = $defaults[$key];
            } elseif ($key !== 'default') {
                $params[$key] = $val;
            }
        }

        $parserlib = TikiLib::lib('parser');
        $out = $parserlib->plugin_execute(
            $name,
            $content,
            $params,
            0,
            false,
            [
                'context_format' => 'html',
                'ck_editor' => false,
                'is_html' => 'y'
            ]
        );

        return '~np~' . $out . '~/np~';
    }
}
