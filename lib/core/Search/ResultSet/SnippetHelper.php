<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_ResultSet_SnippetHelper implements Laminas\Filter\FilterInterface
{
    private $length;
    private $formatter;

    public function __construct($length = 240)
    {
        $this->length = (int) 240;
        $this->formatter = new Search_Formatter_ValueFormatter_Snippet([ 'length' => $this->length ]);
    }

    public function filter($content)
    {
        $snippet = $this->formatter->render('', $content, []);

        return $snippet;
    }
}
