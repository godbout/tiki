<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('lib/wizard/wizard.php');

/**
 * The Wizard's namespace handler
 */
class AdminWizardNamespace extends Wizard
{
    public function pageTitle()
    {
        return tra('Set up Namespace');
    }
    public function isEditable()
    {
        return true;
    }
    public function isVisible()
    {
        global	$prefs;

        return $prefs['namespace_enabled'] === 'y';
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

        // Only show "hide namespace in structures" option, if structures are active
        $isStructures = isset($prefs['feature_wiki_structure']) && $prefs['feature_wiki_structure'] === 'y' ? true : false;
        $smarty->assign('isStructures', $isStructures);

        return true;
    }

    public function getTemplate()
    {
        $wizardTemplate = 'wizard/admin_namespace.tpl';

        return $wizardTemplate;
    }

    public function onContinue($homepageUrl)
    {
        // Run the parent first
        parent::onContinue($homepageUrl);
    }
}
