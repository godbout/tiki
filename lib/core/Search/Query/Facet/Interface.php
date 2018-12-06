<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

interface Search_Query_Facet_Interface
{
	function getLabel();
	function setLabel($label);
	function getName();
	function setName($name);
	function getField();
	function setRenderCallback($callback);
	function render($value);
	function getType();
}
