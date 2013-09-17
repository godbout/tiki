<?php
// (c) Copyright 2002-2013 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

abstract class Wizard 
{
	protected $returnUrl;
	
	/**
	 * This is method onSetupPage
	 *
	 * @param mixed $homepageUrl This is a description
	 * @return bool true if page should be shown. If false, the wizard will skip the page
	 *
	 */	
	function onSetupPage ($homepageUrl) {
		$this->returnUrl = $homepageUrl;
		return true;
	}
	
	function onContinue () {
		// Save the user selection for showing the wizard on login or not
		$showOnLogin = ( isset($_REQUEST['showOnLogin']) && $_REQUEST['showOnLogin'] == 'on' ) ? 'y' : 'n';

		require_once('lib/wizard/wizardlib.php');
		$wizardlib = new WizardLib();
		$wizardlib->showOnLogin($showOnLogin);	
	}
}