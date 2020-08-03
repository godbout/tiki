<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Filter\Control;

class TextField implements Control
{
    private $fieldName;
    private $value = '';

    public function __construct($name)
    {
        $this->fieldName = $name;
    }

    public function applyInput(\JitFilter $input)
    {
        $this->value = $input->{$this->fieldName}->text();
    }

    public function getQueryArguments()
    {
        if ($this->value) {
            return [$this->fieldName => $this->value];
        }

        return [];
    }

    public function getDescription()
    {
        return $this->value ?: null;
    }

    public function getId()
    {
        return $this->fieldName;
    }

    public function isUsable()
    {
        return true;
    }

    public function hasValue()
    {
        return ! empty($this->value);
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        $smarty = \TikiLib::lib('smarty');
        $smarty->assign('control', [
            'field' => $this->fieldName,
            'value' => $this->value,
        ]);

        return $smarty->fetch('filter_control/text_field.tpl');
    }
}
