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
class AdminWizardEditorType extends Wizard
{
    public function pageTitle()
    {
        return tra('Select Editor type');
    }
    public function isEditable()
    {
        return true;
    }

    public function onSetupPage($homepageUrl)
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');

        // Run the parent first
        parent::onSetupPage($homepageUrl);

        $showPage = true;

        $editorType = isset($prefs['feature_wysiwyg']) && $prefs['feature_wysiwyg'] === 'y' ? 'wysiwyg' : 'text';
        $smarty->assign('editorType', $editorType);

        return $showPage;
    }

    public function getTemplate()
    {
        $wizardTemplate = 'wizard/admin_editor_type.tpl';

        return $wizardTemplate;
    }

    public function onContinue($homepageUrl)
    {
        $tikilib = TikiLib::lib('tiki');

        // Run the parent first
        parent::onContinue($homepageUrl);

        $editorType = $_REQUEST['editorType'];
        switch ($editorType) {
            case 'text':
                $tikilib->set_preference('feature_wysiwyg', 'n');

                break;

            case 'wysiwyg':
                $tikilib->set_preference('feature_wysiwyg', 'y');

                break;
        }
    }
}
