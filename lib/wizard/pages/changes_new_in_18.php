<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('lib/wizard/wizard.php');

/**
 * The Wizard's language handler
 */
class ChangesWizardNewIn18 extends Wizard
{
	function pageTitle()
	{
		return tra('New in Tiki 18 (LTS)');
	}

	function isEditable()
	{
		return true;
	}

	function onSetupPage($homepageUrl)
	{
		global $prefs;
		$smarty = TikiLib::lib('smarty');
		$addonprefs = TikiLib::lib('prefs')->getPackagePrefs();
		$smarty->assign('addonprefs', $addonprefs);

		// Run the parent first
		parent::onSetupPage($homepageUrl);

		$showPage = true;

		// Show if any more specification is needed

		return $showPage;
	}

	function getTemplate()
	{
		$wizardTemplate = 'wizard/changes_new_in_18.tpl';

		return $wizardTemplate;
	}

	function onContinue($homepageUrl)
	{
		global $tikilib;

		// Run the parent first
		parent::onContinue($homepageUrl);
	}
}
