<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_chartjs_info()
{
	return [
		'name' => tr('Chart JS'),
		'documentation' => 'PluginChartJS',
		'description' => tra('Create a JS Chart'),
		'prefs' => ['wikiplugin_chartjs'],
		'body' => tr('JSON encoded array for data and options.'),
		'tags' => ['advanced'],
		'introduced' => 16,
		'params' => [
			'id' => [
				'name' => tra('Chart Id'),
				'description' => tr('A custom ID for the chart.'),
				'filter' => 'text',
				'default' => 'tikiChart1, tikiChart2 etc',
				'since' => '16.0',
			],
			'type' => [
				'name' => tra('Chart Type'),
				'description' => tr('The type of chart. Currently works with pie, bar and doughnut'),
				'filter' => 'text',
				'default' => 'pie',
				'since' => '16.0',
			],
			'height' => [
				'name' => tra('Chart Height'),
				'description' => tr('The height of the chart in px'),
				'filter' => 'text',
				'default' => '200',
				'since' => '16.0',
			],
			'width' => [
				'name' => tra('Chart Width'),
				'description' => tr('The width of the chart in px'),
				'filter' => 'text',
				'default' => '200',
				'since' => '16.0',
			],
			'values' => [
				'name' => tra('Chart data values'),
				'description' => tr('Colon-separated values for the chart (required if not using JSON encoded data in the plugin body)'),
				'filter' => 'text',
				'default' => '',
				'since' => '16.0',
			],
			'data_labels' => [
				'name' => tra('Chart data labels'),
				'description' => tr('Colon-separated labels for the datasets in the chart. Max 10, if left empty'),
				'filter' => 'text',
				'default' => 'A:B:C:D:E:F:G:H:I:J',
				'since' => '16.0',
			],
			'data_colors' => [
				'name' => tra('Chart colors'),
				'description' => tr('Colon-separated colors for the datasets in the chart. Max 10, if left empty'),
				'filter' => 'text',
				'default' => 'red:blue:green:purple:grey:orange:yellow:black:brown:cyan',
				'since' => '16.0',
			],
			'data_highlights' => [
				'name' => tra('Chart highlight'),
				'description' => tr('Colon-separated color of chart section when highlighted'),
				'filter' => 'text',
				'default' => '',
				'since' => '16.0',
			],
			'debug' => [
				'name' => tra('Debug Mode'),
				'description' => tr('Uses the non-minified version of the chart.js library for easier debugging.'),
				'filter' => 'digits',
				'default' => 0,
				'advanced' => true,
				'since' => '18.3',
			],
		],
		'iconname' => 'pie-chart',
	];
}

function wikiplugin_chartjs($data, $params)
{
	static $instance = 0;
	$instance++;

	if (empty($params['id'])) {
		$params['id'] = "tikiChart$instance";
	}

	//set defaults
	$plugininfo = wikiplugin_chartjs_info();
	$defaults = [];
	foreach ($plugininfo['params'] as $key => $param) {
		$defaults[$key] = $param['default'];
	}
	$params = array_merge($defaults, $params);

	if (empty($params['data_highlights'])) {
		$params['data_highlights'] = $params['data_colors'];
	}

	if (empty(trim($data))) {
		$values = array_filter(explode(':', $params['values']));
		$data_labels = array_filter(explode(':', $params['data_labels']));
		$data_colors = array_filter(explode(':', $params['data_colors']));
		$data_highlights = array_filter(explode(':', $params['data_highlights']));

		if (empty($values)) {
			return tr('Values must be set for chart');
		}

		$data = [
			'labels'   => array_slice($data_labels, 0, count($values)),
			'datasets' => [
				[
					'data'                 => $values,
					'backgroundColor'      => array_slice($data_colors, 0, count($values)),
					'hoverBackgroundColor' => array_slice($data_highlights, 0, count($values)),
				],
			],
		];
		$options = [];
	} else {
		$data = json_decode($data, true);
		if (isset($data['options']) && isset($data['data'])) {
			$options = $data['options'];
			$data = $data['data'];
		}
	}

	$min = $params['debug'] ? '': 'min.';

	TikiLib::lib('header')->add_jsfile("vendor_bundled/vendor/chartjs/Chart.js/Chart.{$min}js")
		->add_jq_onready('
setTimeout(function () {
	var chartjs_' . $params['id'] . ' = new Chart("' . $params['id'] . '", {
		type: "' . $params['type'] . '",
		data: ' . json_encode($data) . ',
		options: ' . json_encode($options) . '
	})
},
500);');

	return '<div class="tiki-chartjs" style="width:' . $params['width'] . 'px;height:' . $params['height'] . 'px">' .
				'<canvas id="' . $params['id'] . '" width="' . $params['width'] . '" height="' . $params['height'] . '">' .
			'</canvas></div>';
}
