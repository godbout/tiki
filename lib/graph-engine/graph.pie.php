<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once 'lib/graph-engine/core.php';

/**
 * PieChartGraphic
 *
 * @uses Graphic
 */
class PieChartGraphic extends Graphic
{
    public $pie_data;

    public function __construct()
    {
        parent::__construct();
        $this->pie_data = [];
    }

    public function getRequiredSeries()
    {
        return [
            'label' => false,
            'value' => true,
            'color' => false,
            'style' => false
        ];
    }

    public function _handleData($data)
    {
        $elements = count($data['value']);

        if (! isset($data['color'])) {
            $data['color'] = [];
            for ($i = 0; $elements > $i; ++$i) {
                $data['color'][] = $this->_getColor();
            }
        }

        if (! isset($data['style'])) {
            for ($i = 0; $elements > $i; ++$i) {
                $data['style'][] = 'FillStroke-' . $data['color'][$i];
            }
        }

        if (isset($data['label'])) {
            foreach ($data['label'] as $key => $label) {
                $this->addLegend($data['color'][$key], $label);
            }
        }

        $total = array_sum($data['value']);
        foreach ($data['value'] as $key => $value) {
            if (is_numeric($value)) {
                $this->pie_data[] = [$data['style'][$key], $value / $total * 360];
            }
        }

        return true;
    }

    public function _drawContent(&$renderer)
    {
        $layout = $this->_layout();
        $centerX = $layout['pie-center-x'];
        $centerY = $layout['pie-center-y'];
        $radius = $layout['pie-radius'];

        $base = 0;

        foreach ($this->pie_data as $info) {
            list($style, $degree) = $info;
            $renderer->drawPie(
                $centerX,
                $centerY,
                $radius,
                $base,
                $base + $degree,
                $renderer->getStyle($style)
            );

            $base += $degree;
        }
    }

    public function _drawLegendBox(&$renderer, $color)
    {
        $renderer->drawRectangle(0, 0, 1, 1, $renderer->getStyle("FillStroke-$color"));
    }

    public function _default()
    {
        return array_merge(
            parent::_default(),
            [
                'pie-center-x' => 0.5,
                'pie-center-y' => 0.5,
                'pie-radius' => 0.4
            ]
        );
    }
}
