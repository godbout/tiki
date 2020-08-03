<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Yaml;

use Tiki\Yaml\Filter\FilterInterface;

class Directives
{
    protected $path;
    protected $filter;

    public function __construct(FilterInterface $filter = null, $path = null)
    {
        if (is_null($path)) {
            $this->path = TIKI_PATH;
        } else {
            $this->path = $path;
        }

        $this->filter = $filter;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    protected function applyDirective($directive, &$value, $key)
    {
        $class = "\\Tiki\\Yaml\\Directive\\Directive" . ucfirst($directive);
        if (! class_exists($class, true)) {
            return;
        }
        $directive = new $class();
        $directive->process($value, $key, ['path' => $this->path]);
    }

    protected function directiveFromValue($value)
    {
        if (is_array($value)) {
            $value = array_values($value)[0];
        }
        $directive = substr($value, 1, strpos($value, " ") - 1);

        return $directive;
    }

    protected function valueIsDirective($value)
    {
        $testValue = $value;
        if (is_array($value) && ! empty($value)) {
            $testValue = array_values($value)[0];
        }

        if (is_string($testValue) && (strncmp('!', $testValue, 1) == 0)) {
            // Wiki syntax can often start with ! for titles and so the following checks are needed to reduce
            // conflict possibility with YAML user-defined data type extensions syntax
            if (! ctype_lower(substr($testValue, 1, 1))) {
                return false;
            }

            $class = "\\Tiki\\Yaml\\Directive\\Directive" . ucfirst($this->directiveFromValue($testValue));
            if (! class_exists($class)) {
                return false;
            }

            return true;
        }

        return false;
    }

    protected function map(&$value, $key)
    {
        if ($this->valueIsDirective($value)) {
            if ($this->filter instanceof FilterInterface) {
                $this->filter->filter($value);
            }
            $this->applyDirective($this->directiveFromValue($value), $value, $key);
        } else {
            if (is_array($value)) {
                array_walk($value, [$this, "map"]);
            }
        }
    }

    public function process(&$yaml)
    {
        array_walk($yaml, [$this, "map"]);
    }
}
