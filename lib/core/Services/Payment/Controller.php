<?php
// (c) Copyright 2002-2013 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: Controller.php 46965 2013-08-02 19:05:59Z jonnybradley $

class Services_Payment_Controller
{

	function setUp()
	{
		Services_Exception_Disabled::check('payment_feature', 'wikiplugin_addtocart');
	}

	function action_addtocart($input)
	{
		$cartlib = TikiLib::lib('cart');

		$params = $input->params->asArray();

		return $cartlib->add_to_cart($params, $input);
	}

}
