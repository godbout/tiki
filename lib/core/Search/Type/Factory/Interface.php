<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

interface Search_Type_Factory_Interface
{
    // tokenized - indexed - unstored in database
    public function plaintext($value);
    // tokenized - indexed - unstored in database
    public function plainmediumtext($value);
    // wiki parsed before indexed - tokenized - indexed - unstored in database
    public function wikitext($value);
    // not tokenized - indexed - stored in database
    public function timestamp($value, $dateOnly = false);
    // not tokenized - indexed - stored in database
    public function identifier($value);
    // not tokenized - indexed - stored in database
    public function numeric($value);
    // tokenized - indexed - unstored in database
    public function multivalue($values);
    // tokenized - indexed - unstored in database
    public function object($values);
    // tokenized - indexed - unstored in database
    public function nested($values);
    // tokenized - indexed - stored in database
    public function sortable($value);
    // tokenized - using Elasticsearch simple analyzer without stemming etc.
    // useful in wildcard searches or when stemming is not desired. e.g. *leslie* doesn't match leslie.
    public function simpletext($value);
    // tokenized - indexed - unstored in database (?)
    public function geopoint($value);
    // like object but - not indexed - not mapped
    public function json($value);
}
