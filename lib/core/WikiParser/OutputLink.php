<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class WikiParser_OutputLink
{
    private $description;
    private $identifier;
    private $language;
    private $qualifier;
    private $anchor;

    private $namespace;
    private $namespaceSeparator;

    private $externals = [];
    private $handlePlurals = false;

    private $wikiLookup;
    private $wikiBuilder = 'trim';

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    public function setNamespace($namespace, $separator)
    {
        $this->namespace = $namespace;
        $this->namespaceSeparator = $separator;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setQualifier($qualifier)
    {
        $this->qualifier = $qualifier;
    }

    public function setLanguage($lang)
    {
        $this->language = $lang;
    }

    public function setWikiLookup($lookup)
    {
        $this->wikiLookup = $lookup;
    }

    public function setWikiLinkBuilder($builder)
    {
        $this->wikiBuilder = $builder;
    }

    public function setExternals(array $externals)
    {
        $this->externals = $externals;
    }

    public function setHandlePlurals($handle)
    {
        $this->handlePlurals = (bool) $handle;
    }

    public function setAnchor($anchor)
    {
        $this->anchor = $anchor;
    }

    public function getHtml($ck_editor = false)
    {
        $page = $this->identifier;
        $description = $this->identifier;
        if ($this->description) {
            $description = $this->description;
        }
        $pageId = substr($page, 0, 158);
        if ($link = $this->handleExternal($page, $description, $class)) {
            return $this->outputLink(
                $description,
                [
                        'href' => $link . $this->anchor,
                        'class' => $class,
                ]
            );
        } elseif ($this->namespace && (($info = $this->findWikiPage("{$this->namespace}{$this->namespaceSeparator}$pageId")) || $ck_editor)) {
            // When currently displayed page is in a namespace, interpret links as within namespace as a priority
            if (! empty($info['pageName'])) {
                $page = $info['pageName'];
                $pageId = substr($page, 0, 158);
            }

            return $this->outputLink(
                $description,
                [
                        'href' => call_user_func($this->wikiBuilder, $pageId) . $this->anchor,
                        'title' => $this->getTitle($info),
                        'class' => 'wiki wiki_page',
                ]
            );
        } elseif ($this->qualifier === 'alias') {
            global $prefs;
            $slug = TikiLib::lib('slugmanager')->generate($prefs['wiki_url_scheme'], $page, $prefs['url_only_ascii'] === 'y');

            return $this->outputLink(
                $description,
                [
                    'href' => call_user_func($this->wikiBuilder, $slug) . $this->anchor,
                    'class' => 'wiki wiki_page',
                ]
            );
        } elseif (($info = $this->findWikiPage($pageId)) || $ck_editor) {
            if (! empty($info['pageName'])) {
                $page = $info['pageName'];
                $pageId = substr($page, 0, 158);
            }

            if ($description == $info['pageName']) {
                $description = $this->renderPageName($info);
            }

            return $this->outputLink(
                $description,
                [
                    'href' => call_user_func($this->wikiBuilder, $pageId) . $this->anchor,
                    'title' => $this->getTitle($info),
                    'class' => 'wiki wiki_page',
                ]
            );
        }
        $page = $this->getTargetPage($page);
        $pageId = substr($page, 0, 158);

        return $this->outputLink(
            $description,
            [
                    'href' => $this->getEditLink($pageId),
                    'title' => tra('Create page:') . ' ' . str_replace('"', '%22', $pageId),
                    'class' => 'wiki wikinew text-danger tips',
                ]
        );
    }

    private function outputLink($text, array $attributes)
    {
        if ($this->qualifier) {
            $attributes['class'] .= ' ' . $this->qualifier;
        }

        $string = '<a';
        foreach ($attributes as $attr => $val) {
            $val = TikiLib::lib("parser")->protectSpecialChars($val);
            $string .= " $attr=\"" . TikiLib::lib("parser")->unprotectSpecialChars($val) . '"'; //val CANNOT be html, so force it to non-html
        }

        $string .= '>' . $text . '</a>'; //text can return html, so let parser take care of that

        return $string;
    }

    private function getEditLink($page)
    {
        $url = 'tiki-editpage.php?page=' . urlencode($page);

        if ($this->language) {
            $url .= '&lang=' . urlencode($this->language);
        }

        return $url;
    }

    private function handleExternal(& $page, & $description, & $class)
    {
        $parts = explode(':', $page);

        if (count($parts) == 2) {
            list($token, $remotePage) = $parts;
            $token = strtolower($token);

            if (isset($this->externals[$token])) {
                if ($page == $description) {
                    $description = $remotePage;
                }

                $page = $remotePage;
                $pattern = $this->externals[$token];
                $class = 'wiki ext_page ' . $token;

                return str_replace('$page', rawurlencode($page), $pattern);
            }
        }
    }

    private function renderPageName($info)
    {
        global $prefs;

        if (! isset($info['namespace_parts'])) {
            return $info['pageName'];
        }

        $out = '';

        if (end($info['namespace_parts']) == $info['baseName']) {
            array_pop($info['namespace_parts']);
        }

        $last = count($info['namespace_parts']) - 1;
        foreach ($info['namespace_parts'] as $key => $part) {
            if ($prefs['namespace_force_links'] == 'y') {
                break;
            }
            $class = 'namespace';
            if ($key === 0) {
                $class .= ' first';
            }
            if ($key === $last) {
                $class .= ' last';
            }
            $out .= "<span class=\"$class\">$part</span>";
        }

        return $out . $info['baseName'];
    }

    private function findWikiPage($page)
    {
        if (! $this->wikiLookup) {
            return;
        }

        if ($info = call_user_func($this->wikiLookup, $page)) {
            return $info;
        } elseif ($alternate = $this->handlePlurals($page)) {
            return call_user_func($this->wikiLookup, $alternate);
        }
    }

    private function handlePlurals($page)
    {
        if (! $this->handlePlurals) {
            return;
        }

        $alternate = $page;
        // Plurals like policy / policies
        $alternate = preg_replace("/ies$/", "y", $alternate);
        // Plurals like address / addresses
        $alternate = preg_replace("/sses$/", "ss", $alternate);
        // Plurals like box / boxes
        $alternate = preg_replace("/([Xx])es$/", "$1", $alternate);
        // Others, excluding ending ss like address(es)
        $alternate = preg_replace("/([A-Za-rt-z])s$/", "$1", $alternate);

        if ($alternate != $page) {
            return $alternate;
        }
    }

    private function getTargetPage($page)
    {
        if ($this->namespace) {
            return "{$this->namespace}{$this->namespaceSeparator}$page";
        }

        return $page;
    }

    private function getTitle($info)
    {
        if (! empty($info['description'])) {
            return $info['description'];
        } elseif (! empty($info['prettyName'])) {
            return $info['prettyName'];
        }

        return $info['pageName'];
    }
}
