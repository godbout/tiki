<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

interface Search_Query_Facet_Interface
{
    public function getLabel();
    public function setLabel($label);
    public function getName();
    public function setName($name);
    public function getField();
    public function setRenderCallback($callback);
    public function render($value);
    public function getType();
}
