<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('lib/wizard/wizard.php');

/**
 * The Wizard's editor type selector handler
 */
class AdminWizardStructures extends Wizard
{
    public function pageTitle()
    {
        return tra('Set up Structures');
    }
    public function isEditable()
    {
        return true;
    }
    public function isVisible()
    {
        global	$prefs;

        return isset($prefs['feature_wiki_structure']) && $prefs['feature_wiki_structure'] === 'y' ? true : false;
    }

    public function onSetupPage($homepageUrl)
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        // Run the parent first
        parent::onSetupPage($homepageUrl);

        if (! $this->isVisible()) {
            return false;
        }

        $isCategories = isset($prefs['feature_categories']) && $prefs['feature_categories'] === 'y' ? true : false;
        $smarty->assign('isCategories', $isCategories);

        return true;
    }

    public function getTemplate()
    {
        $wizardTemplate = 'wizard/admin_structures.tpl';

        return $wizardTemplate;
    }

    public function onContinue($homepageUrl)
    {
        // Run the parent first
        parent::onContinue($homepageUrl);
    }
}
