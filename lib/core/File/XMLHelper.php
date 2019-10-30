<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\File;

use SimpleXMLElement;

/**
 * Class XMLHelper
 * Class responsible for providing XML helper functions
 * @package Tiki\File
 */
class XMLHelper
{

	/**
	 * Append a simpleXMLElement to another simpleXMLElement.
	 * This is not supported using legacy simpleXMLELement
	 * @param SimpleXMLElement $root
	 * @param SimpleXMLElement $child
	 */
	public static function appendElement(SimpleXMLElement $root, SimpleXMLElement $child)
	{
		$node = $root->addChild($child->getName(), (string) $child);

		foreach($child->attributes() as $attr => $value) {
			$node->addAttribute($attr, $value);
		}

		foreach($child->children() as $ch) {
			self::appendElement($node, $ch);
		}
	}
}
