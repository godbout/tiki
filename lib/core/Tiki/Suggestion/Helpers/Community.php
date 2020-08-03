<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Suggestion\Helpers;

use Tiki\Suggestion\SuggestionRulesInterface as SuggestionRules;

class Community implements SuggestionRules
{
    public function parser()
    {
        $message = tra('Participate in tiki community:');
        $message .= '<a target="_blank" title="' . tra('Tiki Community') . '" alt="' . tra('Tiki Community') . '" href="https://tiki.org/Community">https://tiki.org/Community</a>';

        return $message;
    }
}
