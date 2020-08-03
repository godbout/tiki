<?php

namespace Tiki\MailIn\Provider;

use Tiki\MailIn\Action;

class TrackerProvider implements ProviderInterface
{
    public function isEnabled()
    {
        global $prefs;

        return $prefs['feature_trackers'] == 'y';
    }

    public function getType()
    {
        return 'tracker';
    }

    public function getLabel()
    {
        return tr('Store mail in Tracker');
    }

    public function getActionFactory(array $acc)
    {
        return new Action\DirectFactory('Tiki\MailIn\Action\Tracker', [
            'attachments' => $acc['attachments'],
            'trackerId' => $acc['trackerId'],
        ]);
    }
}
