<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Index_AbstractIndexDecorator implements Search_Index_Interface
{
    protected $parent;

    public function __construct(Search_Index_Interface $index)
    {
        $this->parent = $index;
    }

    public function addDocument(array $document)
    {
        return $this->parent->addDocument($document);
    }

    public function invalidateMultiple(array $query)
    {
        return $this->parent->invalidateMultiple($query);
    }

    public function endUpdate()
    {
        return $this->parent->endUpdate();
    }

    public function find(Search_Query_Interface $query, $resultStart, $resultCount)
    {
        return $this->parent->find($query, $resultStart, $resultCount);
    }

    public function getTypeFactory()
    {
        return $this->parent->getTypeFactory();
    }

    public function optimize()
    {
        return $this->parent->optimize();
    }

    public function destroy()
    {
        return $this->parent->destroy();
    }

    public function exists()
    {
        return $this->parent->exists();
    }

    public function getMatchingQueries(array $document)
    {
        return $this->parent->getMatchingQueries($document);
    }

    public function store($name, Search_Expr_Interface $expr)
    {
        return $this->parent->store($name, $expr);
    }

    public function unstore($name)
    {
        return $this->parent->unstore($name);
    }

    public function getRealIndex()
    {
        if ($this->parent instanceof self) {
            return $this->parent->getRealIndex();
        }

        return $this->parent;
    }
}
