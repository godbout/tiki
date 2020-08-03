<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// George G. Geller
// January 24, 2004
// Revised: October 11, 2004
//
// doc/devtools/ggg-trace.php
// A simple debugging tool.
// Use in your php like this:
// require_once('doc/devtools/ggg-trace.php');
// $ggg_tracer->outln(__FILE__." line: ".__LINE__);
//
// In a Unix shell, tail -f ggg-trace.out and watch what your php is doing.
//
// Print variables (nested arrays are printed recursively) like this:
// $ggg_tracer->out('$arrayName = '); $ggg_tracer->outvar($arrayName);

//
// BE SURE TO DELETE OR COMMENT OUT ALL REFERENCES TO THIS BEFORE RELEASING
// YOUR PHP!
//

/**
 *
 */
class ggg_trace
{
    public $fp;

    /**
     * @param string $nameStr
     */
    public function __construct($nameStr = 'ggg-trace.out')
    {
        register_shutdown_function([&$this, '_ggg_trace']); // the & is important
        $this->fp = fopen($nameStr, 'a');
        fwrite($this->fp, "\n");
        // e.g. 20031231 17:00:20
        fwrite($this->fp, '*' . date('Ymd G:i:s') . "*Starting*****************************************************\n");

        // print date("Ymd G:i:s<br>",time()); // e.g. 20031231 17:00:20
    }

    /**
     * @param string $outStr
     */
    public function out($outStr = '')
    {
        fwrite($this->fp, "$outStr");
    }

    /**
     * @param string $outStr
     */
    public function outln($outStr = '')
    {
        fwrite($this->fp, "$outStr\n");
    }

    /**
     * @param $var
     * @param int $indent
     */
    public function outvar($var, $indent = 0)
    {
        if ($indent > 8) {
            fwrite($this->fp, "Too many levels of recursion! \n");

            return;
        }

        $spaces = sprintf('%' . $indent . 's', '');
        fwrite($this->fp, $spaces . $var . "\n");

        if (is_array($var)) {
            $indent++;
            $spaces = sprintf('%' . $indent . 's', '');
            foreach ($var as $key => $val) {
                if ($key === 'GLOBALS' && is_array($val)) {
                    // In case we are called with $ggg_tracer->outvar($GLOBALS);
                    // and we don't check here, we get an infinite recursion.
                    // If another array has an element called GLOBALS, oh well.
                    fwrite($this->fp, "ggg-trace.php, line 62: Found GLOBALS array, not recursing. \n");
                } elseif (is_array($val)) {
                    $this->out($spaces . "$key = ");
                    $this->outvar($val, $indent);
                } else {
                    fwrite($this->fp, $spaces . $key . '=>' . $val . "\n");
                }
            }
        }
    }

    public function _ggg_trace()
    {
        fwrite($this->fp, '*' . date('Ymd G:i:s') . "*Finishing****************************************************\n");
        fclose($this->fp);
    }
}

$ggg_traceFiles = new ggg_trace('ggg-traceFiles.out');
$ggg_traceFiles->outln(__FILE__);

$ggg_tracer = new ggg_trace();
// $ggg_tracer->outln("Tracer initialized in ggg-trace.php...");
