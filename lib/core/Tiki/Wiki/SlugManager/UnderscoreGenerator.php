<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Wiki\SlugManager;

class UnderscoreGenerator implements Generator
{
    public function getName()
    {
        return 'underscore';
    }

    public function getLabel()
    {
        return tr('Replace spaces with underscores');
    }

    public function generate($pageName, $suffix = null)
    {
        $slug = preg_replace('/\s+/', '_', trim($pageName));

        if ($suffix) {
            $slug .= '_' . $suffix;
        }

        return $slug;
    }

    public function degenerate($slug)
    {
        return preg_replace('/_+/', ' ', trim($slug));
    }
}
