<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$


class Multilingual_MachineTranslation_Cache implements Multilingual_MachineTranslation_Interface
{
    private $handler;
    private $hash;

    public function __construct(Multilingual_MachineTranslation_Interface $handler, $hash)
    {
        $this->handler = $handler;
        $this->hash = $hash;
    }

    public function getSupportedLanguages()
    {
        return $this->handler->getSupportedLanguages();
    }

    public function translateText($text)
    {
        $cachelib = TikiLib::lib('cache');

        if ($result = $cachelib->getCached($text . $this->hash, 'translation')) {
            return $result;
        }

        $result = $this->handler->translateText($text);

        $cachelib->cacheItem($text, $result, 'translation');

        return $result;
    }
}
