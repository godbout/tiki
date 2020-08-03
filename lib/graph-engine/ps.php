<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/* This library is LGPL
 * written by Louis-Philippe Huberdeau
 *
 * vim: fdm=marker tabstop=4 shiftwidth=4 noet:
 *
 * This file contains the PostScript graphic renderer. (Using PSLib)
 */
require_once('lib/graph-engine/core.php');

class PS_GRenderer extends GRenderer // {{{1
{
    public $ps;
    public $styles;
    public $font;

    public $width;
    public $height;

    public function __construct($format = null, $orientation = 'landscape') // {{{2
    {
        // Null size does not create a graphic.
        $this->styles = [];
        $this->font = null;

        if (! is_null($format)) {
            $size = $this->_getFormat($format, $orientation);
            $this->width = $size[0];
            $this->height = $size[1];

            $this->ps = ps_new();
            ps_open_file($this->ps, '');
            ps_begin_page($this->ps, $this->width, $this->height);

            $this->font = ps_findfont($this->ps, 'Helvetica', '', 0);
        }
    }

    public function addLink($target, $left, $top, $right, $bottom, $title = null) // {{{2
    {
    }

    public function drawLine($x1, $y1, $x2, $y2, $style) // {{{2
    {
        $this->_convertPosition($x1, $y1);
        $this->_convertPosition($x2, $y2);

        ps_setcolor(
            $this->ps,
            'stroke',
            $style['line'][0],
            $style['line'][1],
            $style['line'][2],
            $style['line'][3],
            $style['line'][4]
        );

        ps_setlinewidth($this->ps, $style['line-width']);

        ps_moveto($this->ps, $x1, $y1);
        ps_lineto($this->ps, $x2, $y2);
        ps_stroke($this->ps);
    }

    public function drawRectangle($left, $top, $right, $bottom, $style) // {{{2
    {
        $this->_convertPosition($left, $top);
        $this->_convertPosition($right, $bottom);

        ps_setcolor(
            $this->ps,
            'stroke',
            $style['line'][0],
            $style['line'][1],
            $style['line'][2],
            $style['line'][3],
            $style['line'][4]
        );

        if (isset($style['fill'])) {
            ps_setcolor(
                $this->ps,
                'fill',
                $style['fill'][0],
                $style['fill'][1],
                $style['fill'][2],
                $style['fill'][3],
                $style['fill'][4]
            );
        }

        ps_setlinewidth($this->ps, $style['line-width']);

        ps_rect($this->ps, $left, $top, $right - $left, $bottom - $top);

        if (isset($style['fill'])) {
            ps_fill_stroke($this->ps);
        } else {
            ps_stroke($this->ps);
        }
    }

    public function drawPie($centerX, $centerY, $radius, $begin, $end, $style) // {{{2
    {
        $this->_convertPosition($centerX, $centerY);
        $radius = $radius * min($this->width, $this->height);

        ps_setcolor(
            $this->ps,
            'stroke',
            $style['line'][0],
            $style['line'][1],
            $style['line'][2],
            $style['line'][3],
            $style['line'][4]
        );

        if (isset($style['fill'])) {
            ps_setcolor(
                $this->ps,
                'fill',
                $style['fill'][0],
                $style['fill'][1],
                $style['fill'][2],
                $style['fill'][3],
                $style['fill'][4]
            );
        }

        ps_setlinewidth($this->ps, $style['line-width']);

        ps_moveto($this->ps, $centerX, $centerY);
        ps_arc($this->ps, $centerX, $centerY, $radius, $begin, $end);
        ps_lineto($this->ps, $centerX, $centerY);
        ps_closepath($this->ps);

        if (isset($style['fill'])) {
            ps_fill_stroke($this->ps);
        } else {
            ps_stroke($this->ps);
        }
    }

    public function drawText($text, $left, $right, $height, $style) // {{{2
    {
        $h = $height; // Creating duplicate (temp)
        $this->_convertPosition($left, $height);
        $this->_convertPosition($right, $h);

        ps_setcolor(
            $this->ps,
            'fill',
            $style['fill'][0],
            $style['fill'][1],
            $style['fill'][2],
            $style['fill'][3],
            $style['fill'][4]
        );

        ps_setfont($this->ps, $this->font, $style['font']);
        ps_show_boxed($this->ps, $text, $left, $height - $style['font'], $right - $left, $style['font'], $style['align'], '');
    }

    public function getTextWidth($text, $style) // {{{2
    {
        return ps_stringwidth($this->ps, $text, $this->font, $style['font']) / $this->width;
    }

    public function getTextHeight($style) // {{{2
    {
        return $style['font'] / $this->height;
    }

    public function getStyle($name) // {{{2
    {
        if (isset($this->styles[$name])) {
            return $this->styles[$name];
        }

        return $this->styles[$name] = $this->_findStyle($name);
    }

    public function httpOutput($filename) // {{{2
    {
        ps_end_page($this->ps);
        ps_close($this->ps);

        $buf = ps_get_buffer($this->ps);
        $len = strlen($buf);

        header("Content-type: application/ps");
        header("Content-Length: $len");
        header("Content-Disposition: inline; filename=$name");
        echo $buf;

        ps_delete($this->ps);
    }

    public function writeToStream($stream) // {{{2
    {
        ps_end_page($this->ps);
        ps_close($this->ps);

        $buf = ps_get_buffer($this->ps);
        fwrite($stream, $buf);

        ps_delete($this->ps);
    }

    public function _convertLength($value, $type) // {{{2
    {
        // $type is either 'width' or 'height'
        // $value is a 0-1 float
        return floor($value * $this->$type);
    }

    public function _convertPosition(&$x, &$y) // {{{2
    {
        // Parameters passed by ref!
        $x = $this->_convertLength($x, 'width');
        $y = $this->height - $this->_convertLength($y, 'height');
    }

    public function _findStyle($name) // {{{2
    {
        $parts = explode('-', $name);
        $style = [];

        switch ($parts[0]) {
            case 'Thin':
                $style['line-width'] = 1;
                array_shift($parts);

                break;
            case 'Bold':
                $style['line-width'] = 2;
                array_shift($parts);

                break;
            case 'Bolder':
                $style['line-width'] = 3;
                array_shift($parts);

                break;
            case 'Large':
                $style['font'] = 16;
                array_shift($parts);

                break;
            case 'Small':
                $style['font'] = 8;
                array_shift($parts);

                break;
            case 'Normal':
                array_shift($parts);
                // no break
            default:
                if ($parts[0] == 'Text') {
                    $style['font'] = 12;
                } else {
                    $style['line-width'] = 1;
                }

                break;
        }

        switch ($parts[0]) {
            case 'LineStroke':
                $style['line'] = $this->_getColor($parts[1]);

                break;
            case 'FillStroke':
                $style['fill'] = $this->_getColor($parts[1]);
                $style['line'] = $this->_getColor('Black');

                break;
            case 'Text':
                $style['fill'] = $this->_getColor('Black');
                switch ($parts[1]) {
                    case 'Center':
                        $style['align'] = 'center';

                        break;
                    case 'Right':
                        $style['align'] = 'right';

                        break;
                    case 'Left':
                    default:
                        $style['align'] = 'left';

                        break;
                }

                break;
            default:
                return GRenderer::getStyle($name);
        }

        return $style;
    }

    public function _getColor($name) // {{{2
    {
        $c = [ 'rgb' ];
        $color = $this->_getRawColor(strtolower($name));
        foreach ($color as $col) {
            $c[] = $col / 255;
        }

        $c[] = null;

        return $c;
    }

    public function _getFormat($format, $orientation) // {{{2
    {
        /*
            Taken from lib/pdflib/class.ezpdf.php
            Copyright notices are in that file.
        */
        switch (strtoupper($format)) {
            case '4A0':
                $size = [4767.87, 6740.79];

                break;
            case '2A0':
                $size = [3370.39, 4767.87];

                break;
            case 'A0':
                $size = [2383.94, 3370.39];

                break;
            case 'A1':
                $size = [1683.78, 2383.94];

                break;
            case 'A2':
                $size = [1190.55, 1683.78];

                break;
            case 'A3':
                $size = [841.89, 1190.55];

                break;
            case 'A4':
                $size = [595.28, 841.89];

                break;
            case 'A5':
                $size = [419.53, 595.28];

                break;
            case 'A6':
                $size = [297.64, 419.53];

                break;
            case 'A7':
                $size = [209.76, 297.64];

                break;
            case 'A8':
                $size = [147.40, 209.76];

                break;
            case 'A9':
                $size = [104.88, 147.40];

                break;
            case 'A10':
                $size = [73.70, 104.88];

                break;
            case 'B0':
                $size = [2834.65, 4008.19];

                break;
            case 'B1':
                $size = [2004.09, 2834.65];

                break;
            case 'B2':
                $size = [1417.32, 2004.09];

                break;
            case 'B3':
                $size = [1000.63, 1417.32];

                break;
            case 'B4':
                $size = [708.66, 1000.63];

                break;
            case 'B5':
                $size = [498.90, 708.66];

                break;
            case 'B6':
                $size = [354.33, 498.90];

                break;
            case 'B7':
                $size = [249.45, 354.33];

                break;
            case 'B8':
                $size = [175.75, 249.45];

                break;
            case 'B9':
                $size = [124.72, 175.75];

                break;
            case 'B10':
                $size = [87.87, 124.72];

                break;
            case 'C0':
                $size = [2599.37, 3676.54];

                break;
            case 'C1':
                $size = [1836.85, 2599.37];

                break;
            case 'C2':
                $size = [1298.27, 1836.85];

                break;
            case 'C3':
                $size = [918.43, 1298.27];

                break;
            case 'C4':
                $size = [649.13, 918.43];

                break;
            case 'C5':
                $size = [459.21, 649.13];

                break;
            case 'C6':
                $size = [323.15, 459.21];

                break;
            case 'C7':
                $size = [229.61, 323.15];

                break;
            case 'C8':
                $size = [161.57, 229.61];

                break;
            case 'C9':
                $size = [113.39, 161.57];

                break;
            case 'C10':
                $size = [79.37, 113.39];

                break;
            case 'RA0':
                $size = [2437.80, 3458.27];

                break;
            case 'RA1':
                $size = [1729.13, 2437.80];

                break;
            case 'RA2':
                $size = [1218.90, 1729.13];

                break;
            case 'RA3':
                $size = [864.57, 1218.90];

                break;
            case 'RA4':
                $size = [609.45, 864.57];

                break;
            case 'SRA0':
                $size = [2551.18, 3628.35];

                break;
            case 'SRA1':
                $size = [1814.17, 2551.18];

                break;
            case 'SRA2':
                $size = [1275.59, 1814.17];

                break;
            case 'SRA3':
                $size = [907.09, 1275.59];

                break;
            case 'SRA4':
                $size = [637.80, 907.09];

                break;
            case 'LETTER':
                $size = [612.00, 792.00];

                break;
            case 'LEGAL':
                $size = [612.00, 1008.00];

                break;
            case 'EXECUTIVE':
                $size = [521.86, 756.00];

                break;
            case 'FOLIO':
                $size = [612.00, 936.00];

                break;
        }

        if (strtolower($orientation) == 'landscape') {
            $a = $size[1];
            $size[1] = $size[0];
            $size[0] = $a;
        }

        return $size;
    }
} // }}}1
