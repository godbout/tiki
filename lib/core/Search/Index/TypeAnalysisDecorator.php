<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Index_TypeAnalysisDecorator extends Search_Index_AbstractIndexDecorator
{
    private $identifierClass;
    private $numericClass;
    private $mapping = [];

    public function __construct(Search_Index_Interface $index)
    {
        parent::__construct($index);
        $this->identifierClass = get_class($index->getTypeFactory()->identifier(1));
        $this->numericClass = get_class($index->getTypeFactory()->numeric(1));
    }

    public function addDocument(array $document)
    {
        $new = array_diff_key($document, $this->mapping);
        foreach ($new as $key => $value) {
            $this->mapping[$key] = $value instanceof $this->identifierClass || $value instanceof $this->numericClass;
        }

        return $this->parent->addDocument($document);
    }

    public function getIdentifierFields()
    {
        return array_keys(array_filter($this->mapping));
    }

    public function getFieldCount()
    {
        return count(array_keys($this->mapping));
    }
}
