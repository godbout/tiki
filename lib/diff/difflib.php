<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

require_once(__DIR__ . "/Diff.php");
require_once(__DIR__ . "/Renderer.php");

/* @brief modif tiki for the renderer lib	*/
class Tiki_Text_Diff_Renderer extends Text_Diff_Renderer
{
    protected function _lines($lines, $prefix = '', $suffix = '', $type = '')
    {
        //ADD $suffix
        foreach ($lines as $line) {
            echo "$prefix$line$suffix\n";
        }
    }
    public function render($diff, $singleEdit = false)
    {
        $x0 = $y0 = 0;
        $xi = $yi = 1;
        $block = false;
        $context = [];

        $nlead = $this->_leading_context_lines;
        $ntrail = $this->_trailing_context_lines;

        $this->_startDiff();

        if (! $singleEdit) {
            $diff = $diff->getDiff();
        }

        foreach ($diff as $edit) {
            if (is_a($edit, 'Text_Diff_Op_copy')) {
                if (is_array($block)) {
                    if (count($edit->orig) <= $nlead + $ntrail) {
                        $block[] = $edit;
                    } else {
                        if ($ntrail) {
                            $context = array_slice($edit->orig, 0, $ntrail);
                            $block[] = new Text_Diff_Op_copy($context);
                        }
                        $this->_block($x0, $ntrail + $xi - $x0, $y0, $ntrail + $yi - $y0, $block);
                        $block = false;
                    }
                }
                $context = $edit->orig;
            } else {
                if (! is_array($block)) {
                    //BUG if compare on all the length:                    $context = array_slice($context, count($context) - $nlead);
                    $context = array_slice($context, -$nlead, $nlead);
                    $x0 = $xi - count($context);
                    $y0 = $yi - count($context);
                    $block = [];
                    if ($context) {
                        $block[] = new Text_Diff_Op_copy($context);
                    }
                }
                $block[] = $edit;
            }

            if ($edit->orig) {
                $xi += count($edit->orig);
            }
            if ($edit->final) {
                $yi += count($edit->final);
            }
        }

        if (is_array($block)) {
            $this->_block($x0, $xi - $x0, $y0, $yi - $y0, $block);
        }

        return $this->_endDiff();
    }
}

function diff2($page1, $page2, $type = 'sidediff')
{
    global $tikilib, $prefs;
    if ($type == 'htmldiff') {
        //$search = "#(<[^>]+>|\s*[^\s<]+\s*|</[^>]+>)#";
        $search = "#(<[^>]+>|[,\"':\s]+|[^\s,\"':<]+|</[^>]+>)#";
        preg_match_all($search, $page1, $out, PREG_PATTERN_ORDER);
        $page1 = $out[0];
        preg_match_all($search, $page2, $out, PREG_PATTERN_ORDER);
        $page2 = $out[0];
    } else {
        $page1 = explode("\n", $page1);
        $page2 = explode("\n", $page2);
    }
    $z = new Text_Diff($page1, $page2);
    if ($z->isEmpty()) {
        $html = '';
    } else {
        $context = 2;
        $words = 1;
        if (strstr($type, "-")) {
            list($type, $opt) = explode("-", $type, 2);
            if (strstr($opt, "full")) {
                $context = count($page1);
            }
            if (strstr($opt, "char")) {
                $words = 0;
            }
        }

        if ($type == 'unidiff') {
            require_once('renderer_unified.php');
            $renderer = new Text_Diff_Renderer_unified($context);
        } elseif ($type == 'inlinediff') {
            require_once('renderer_inline.php');
            $renderer = new Text_Diff_Renderer_inline($context, $words);
        } elseif ($type == 'sidediff') {
            require_once('renderer_sidebyside.php');
            $renderer = new Text_Diff_Renderer_sidebyside($context, $words);
        } elseif ($type == 'bytes' && $prefs['feature_actionlog_bytes'] == 'y') {
            require_once('renderer_bytes.php');
            $renderer = new Text_Diff_Renderer_bytes();
        } elseif ($type == 'htmldiff') {
            require_once('renderer_htmldiff.php');
            $renderer = new Text_Diff_Renderer_htmldiff($context, $words);
        } else {
            return "";
        }
        $html = $renderer->render($z);
    }

    return $html;
}

/* @brief compute the characters differences between a list of lines
 * @param $orig array list lines in the original version
 * @param $final array the same lines in the final version
 * @param int $words
 * @param string $function
 * @return array
 */
function diffChar($orig, $final, $words = 0, $function = 'character')
{
    $glue = strpos($function, 'inline') !== false ? "<br />" : "\n";
    if ($words) {
        preg_match_all("/\w+\s+(?=\w)|\w+|\W/u", implode($glue, $orig), $matches);
        $line1 = $matches[0];
        preg_match_all("/\w+\s+(?=\w)|\w+|\W/u", implode($glue, $final), $matches);
        $line2 = $matches[0];
    } else {
        $line1 = preg_split('//u', implode($glue, $orig), -1, PREG_SPLIT_NO_EMPTY);
        $line2 = preg_split('//u', implode($glue, $final), -1, PREG_SPLIT_NO_EMPTY);
    }
    $z = new Text_Diff($line1, $line2);
    if ($z->isEmpty()) {
        return [$orig[0], $final[0]];
    }
    //echo "<pre>";print_r($z);echo "</pre>";

    compileRendererClass($function);
    $new = "Text_Diff_Renderer_$function";
    $renderer = new $new(count($line1));

    return $renderer->render($z);
}

function compileRendererClass($function)
{
    /*
     * The various subclasses of Text_Diff_Renderer have methods whose signatures are incompatible
     * with those of their parents. This raises some warnings which don't matter in production settings.
     *
     * But when running phpunit tests, this causes some failures, because we have configured phpunit
     * to report warnings as failures.
     *
     * Making the methods compatible with each other would be very involved, and might introduce some
     * actual bugs. So instead, temporarily disable warning reporting, just for the compilation of
     * this file.
     */
    global $old_error_reporting_level;
    if (defined('TIKI_IN_TEST')) {
        $old_error_reporting_level = error_reporting(E_ERROR | E_PARSE);
    }

    require_once("renderer_$function.php");

    if (defined('TIKI_IN_TEST')) {
        error_reporting($old_error_reporting_level);
    }
}

/**
 * Find mentions
 *
 * @param $lines
 * @param $state
 * @return array
 */
function findMentions($lines, $state)
{
    $allMatches = [] ;

    if (isset($lines) && is_array($lines)) {
        foreach (array_filter($lines) as $line) {
            preg_match_all("/(?:^|\s)@(\w+)/i", $line, $matches);
            foreach ($matches[0] as $match) {
                $allMatches[] = [
                    'state' => $state,
                    'mention' => trim($match)
                ];
            }
        }
    }

    return $allMatches;
}

/**
 * Find mentions on change content
 *
 * @param $edit
 * @return array
 */
function findMentionsOnChange($edit)
{
    $allMatches = [];

    if ((isset($edit->orig) && is_array($edit->orig)) && (isset($edit->final) && is_array($edit->final))) {
        if (empty($edit->orig[0])) {
            $mentions = findMentions($edit->final, 'new');
            foreach ($mentions as $m) {
                $allMatches[] = $m;
            }
        } else {
            require_once('renderer_inline.php');
            $renderer = new Text_Diff_Renderer_inline(1);
            $html = $renderer->render([$edit], true);

            // remove unnecessary content
            $html = preg_replace("#<tr class=\"diffheader\">(.*?)</tr>#", "", $html);
            $html = preg_replace("#<span class='diffinldel'>(.*?)</span>#", "", $html);
            $html = str_replace(["<tr class='diffbody'>", "</tr>", "<td colspan='3'>", "</td>"], "", $html);
            $html = str_replace(["<span class='diffadded'>", "</span>"], "<ins>", $html);
            $finalContent = explode('<ins>', $html);

            $index = 0;
            foreach ($finalContent as $key => $value) {
                if (($index % 2) == 1) {
                    // new mention
                    $charToAdd = '';
                    $previousMention = $finalContent[$key - 1];
                    if (! empty($previousMention)) {
                        $lastChar = substr($previousMention, -1);
                        if ($lastChar == '@') {
                            $charToAdd = '@';
                        }
                    }

                    $mentions = findMentions([$charToAdd . $value], 'new');
                } else {
                    // old mention
                    $mentions = findMentions([$value], 'old');
                }

                foreach ($mentions as $m) {
                    $allMatches[] = $m;
                }
                $index++;
            }
        }
    }

    return $allMatches;
}
