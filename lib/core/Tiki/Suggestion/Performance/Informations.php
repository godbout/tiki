<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Suggestion\Performance;

use Tiki\Suggestion\SuggestionRulesInterface as SuggestionRules;

class Informations implements SuggestionRules
{
    public function parser()
    {
        $message = tra('Performance issues? Take advantage of the performance challenge:');
        $message .= '<a target="_blank" title="' . tra('Performance') . '" alt="' . tra('Performance') . '" href="https://tiki.org/Performance-challenge">https://tiki.org/Performance-challenge</a>';

        return $message;
    }
}
