<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

interface Search_Formatter_Plugin_Interface
{
    const FORMAT_WIKI = 'wiki';
    const FORMAT_HTML = 'html';
    const FORMAT_ARRAY = 'array';
    const FORMAT_CSV = 'csv';

    public function getFields();

    public function getFormat();

    public function prepareEntry($entry);

    public function renderEntries(Search_ResultSet $entries);
}
