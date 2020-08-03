<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Wiki;

class SlugManager
{
    private $generators = [];
    private $validationCallback;

    public function __construct()
    {
        $table = \TikiDb::get()->table('tiki_pages');
        $this->validationCallback = function ($slug) use ($table) {
            return $table->fetchCount(['pageSlug' => $slug]) > 0;
        };
    }

    public function setValidationCallback(callable $callback)
    {
        $this->validationCallback = $callback;
    }

    public function addGenerator(SlugManager\Generator $generator)
    {
        $this->generators[$generator->getName()] = $generator;
    }

    public function getOptions()
    {
        return array_map(function ($generator) {
            return $generator->getLabel();
        }, $this->generators);
    }

    /**
     * @param $generator
     * @param $pageName
     * @param bool $asciiOnly
     * @param bool $ignoreCounter If true, generated alias won't contain the counter in case of multiple pages with same slug
     * @throws \Exception
     * @return mixed
     */
    public function generate($generator, $pageName, $asciiOnly = false, $ignoreCounter = false)
    {
        $exists = $this->validationCallback;

        if ($asciiOnly) {
            $pageName = \TikiLib::lib('tiki')->take_away_accent($pageName);
            $pageName = preg_replace('/[^\w-]+/', ' ', $pageName);    // remove other non-word chars and replace with a space
        }

        $impl = $this->generators[$generator];

        $slug = $impl->generate($pageName);
        if ($ignoreCounter) {
            return $slug;
        }

        $counter = 2;
        while ($exists($slug)) {
            $slug = $impl->generate($pageName, $counter++);
        }

        return $slug;
    }

    public function degenerate($generator, $slug)
    {
        $impl = $this->generators[$generator];

        return $impl->degenerate($slug);
    }
}
