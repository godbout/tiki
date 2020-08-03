<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class JisonParser_Phraser_Handler extends JisonParser_Phraser
{
    public $chars = [];
    public $words = [];
    public $currentWord = -1;
    public $wordsChars = [];
    public $indexes = [];
    public $parsed = '';
    public $cache = [];

    public $cssClassStart = '';
    public $cssClassMiddle = '';
    public $cssClassEnd = '';

    public function setCssWordClasses($classes = [])
    {
        $classes = array_merge(
            [
                'start' => '',
                'middle' => '',
                'end' => ''
            ],
            $classes
        );

        $this->cssClassStart = $classes['start'];
        $this->cssClassMiddle = $classes['middle'];
        $this->cssClassEnd = $classes['end'];

        return $this;
    }

    public function tagHandler($tag)
    {
        return $tag;
    }

    public function wordHandler($word)
    {
        $this->currentWord++;
        $this->words[] = $word;

        for ($i = 0, $end = count($this->indexes); $i < $end; $i++) {
            if (empty($this->indexes[$i]['ended'])) {
                if ($this->currentWord >= $this->indexes[$i]['start']
                        && $this->currentWord <= $this->indexes[$i]['end']
                ) {
                    $word = '<span class="phrase phrase' . $i . (! empty($this->cssClassMiddle) ? ' ' . $this->cssClassMiddle . ' ' . $this->cssClassMiddle . $i : '') . '" style="border: none;">' . $word . '</span>';
                }

                if ($this->currentWord == $this->indexes[$i]['start']) {
                    $word = '<span class="phraseStart phraseStart' . $i . (! empty($this->cssClassStart) ? ' ' . $this->cssClassStart . ' ' . $this->cssClassStart . $i : '') . '" style="border: none; font-weight: bold;"></span>' . $word;
                }

                if ($this->currentWord == $this->indexes[$i]['end']) {
                    if (empty($this->wordsChars[$this->currentWord])) {
                        $this->indexes[$i]['ended'] = true;
                        $word .= '<span class="phraseEnd phraseEnd' . $i . (! empty($this->cssClassEnd) ? ' ' . $this->cssClassEnd . ' ' . $this->cssClassEnd . $i : '') . '" style="border: none;"></span>';
                    } else {
                        $word = '<span class="phrase phrase' . $i . (! empty($this->cssClassMiddle) ? ' ' . $this->cssClassMiddle . ' ' . $this->cssClassMiddle . $i : '') . '" style="border: none;">' . $word . '</span>';
                    }
                }
            }
        }

        return $word;
    }

    public function charHandler($char)
    {
        if (empty($this->wordsChars[$this->currentWord])) {
            $this->wordsChars[$this->currentWord] = "";
        }

        //this line attempts to solve some character translation problems
        $char = iconv('UTF-8', 'ISO-8859-1', utf8_encode($char));

        $this->wordsChars[$this->currentWord] .= $char;
        $this->chars[] = $char;

        for ($i = 0, $end = count($this->indexes); $i < $end; $i++) {
            if (empty($this->indexes[$i]['ended'])) {
                if ($this->currentWord >= $this->indexes[$i]['start']) {
                    $char = '<span class="phrases phrase' . $i . (! empty($this->cssClassMiddle) ? ' ' . $this->cssClassMiddle . ' ' . $this->cssClassMiddle . $i : '') . '" style="border: none;">' . $char . '</span>';

                    if ($this->currentWord == $this->indexes[$i]['end']) {
                        if (! empty($this->wordsChars[$this->currentWord])) {
                            $this->indexes[$i]['ended'] = true;
                            $char = $char . '<span class="phraseEnd phraseEnd' . $i . (! empty($this->cssClassEnd) ? ' ' . $this->cssClassEnd . ' ' . $this->cssClassEnd . $i : '') . '" style="border: none;"></span>';
                        }
                    }
                }
            }
        }


        return $char;
    }

    public function isUnique($parent, $phrase)
    {
        $parentWords = $this->sanitizeToWords($parent);
        $phraseWords = $this->sanitizeToWords($phrase);

        $this->clearIndexes();

        $this->addIndexes($parentWords, $phraseWords);

        if (count($this->indexes) > 1) {
            return false;
        }

        return true;
    }

    public function findPhrases($parent, $phrases)
    {
        $parentWords = $this->sanitizeToWords($parent);
        $phrasesWords = [];

        $this->clearIndexes();

        foreach ($phrases as $phrase) {
            $phraseWords = $this->sanitizeToWords($phrase);
            $this->addIndexes($parentWords, $phraseWords);
            $phrasesWords[] = $phraseWords;
        }

        if (! empty($this->indexes)) {
            $parent = $this->parse($parent);
        }

        return $parent;
    }

    public function clearIndexes()
    {
        $this->indexes = [];
    }

    public function addIndexes($parentWords, $phraseWords)
    {
        $phraseLength = count($phraseWords) - 1;
        $phraseConcat = implode($phraseWords, '|');
        $parentConcat = implode($parentWords, '|');

        $boundaries = explode($phraseConcat, $parentConcat);

        //We may not have a match
        if (count($boundaries) == 1 && strlen($boundaries[0]) == strlen($parentConcat)) {
            return [];
        }

        for ($i = 0, $j = count($boundaries); $i < $j; $i++) {
            $boundaryLength = substr_count($boundaries[$i], '|');

            $this->indexes[] = [
                    'start' => min(count($parentWords) - count($phraseWords), $boundaryLength),
                    'end' => min(count($parentWords), $boundaryLength + $phraseLength)
            ];

            $i++;
        }
    }

    public static function hasPhrase($parent, $phrase)
    {
        $parent = self::sanitizeToWords(utf8_encode($parent));
        $phrase = self::sanitizeToWords(utf8_encode($phrase));

        $parent = implode('|', $parent);
        $phrase = implode('|', $phrase);

        return (strpos($parent, $phrase) !== false);
    }

    public static $sanitizedWords;

    public static function sanitizeToWords($html)
    {
        if (isset(self::$sanitizedWords[$html])) {
            return self::$sanitizedWords[$html];
        }

        $sanitized = preg_replace('/<(.|\n)*?>/', ' ', $html);
        $sanitized = preg_replace('/\W/', ' ', $sanitized);
        $sanitized = explode(" ", $sanitized);
        $sanitized = array_values(array_filter($sanitized, 'strlen'));

        self::$sanitizedWords[$html] = $sanitized;

        return $sanitized;
    }

    public static function superSanitize($html)
    {
        return utf8_encode(implode('', self::sanitizeToWords($html)));
    }
}
