<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('lib/wizard/wizard.php');

/**
 * Show the profiles choices
 */
class ProfilesWizardDemoProjectManagement extends Wizard
{
	function pageTitle()
	{
		return tra('Demo of Cases in Project Management');
	}
	function isEditable()
	{
		return false;
	}

	function onSetupPage($homepageUrl)
	{
		global $prefs, $TWV;
		$smarty = TikiLib::lib('smarty');
		// Run the parent first
		parent::onSetupPage($homepageUrl);

		$smarty->assign('tikiMajorVersion', substr($TWV->version, 0, 2));

		return true;
	}

	function getTemplate()
	{
		$wizardTemplate = 'wizard/profiles_demo_cases_in_project_management.tpl';
		return $wizardTemplate;
	}

	function onContinue($homepageUrl)
	{
		// Run the parent first
		parent::onContinue($homepageUrl);
	}
}
