<?php
declare(strict_types = 1);
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

require_once dirname(__DIR__, 4) . '/lib/CactiRrdGraphOptions.php';

test('graph options retain plugin values and normalize scalar inputs', function () {
	$options = CactiRrdGraphOptions::resolve([
		'export'          => '1',
		'export_filename' => '/tmp/export.png',
		'graph_end'       => 200.0,
		'graph_height'    => '125.5',
		'graph_start'     => '100',
		'graph_theme'     => 'classic',
		'graph_width'     => 425,
		'image_format'    => 'png',
		'output_flag'     => '3',
		'plugin_option'   => 'retained',
	]);

	expect($options)
		->toMatchArray([
			'export'          => '1',
			'export_filename' => '/tmp/export.png',
			'graph_end'       => 200,
			'graph_height'    => 125.5,
			'graph_start'     => 100,
			'graph_width'     => 425,
			'output_flag'     => 3,
			'plugin_option'   => 'retained',
		]);
});

test('graph options accept every supported flag and path type', function () {
	$options = CactiRrdGraphOptions::resolve([
		'export'          => true,
		'export_csv'      => 1,
		'get_error'       => 'true',
		'graph_nolegend'  => false,
		'graphv'          => 0,
		'print_source'    => '0',
		'export_filename' => '',
		'export_realtime' => '/tmp/realtime.png',
		'output_filename' => '/tmp/output.png',
		'image_format'    => 'svg+xml',
	]);

	expect($options)->toHaveCount(10)
		->and(CactiRrdGraphOptions::resolve([]))->toBe([]);
});

test('graph options reject invalid integers', function (mixed $value) {
	CactiRrdGraphOptions::resolve(['graph_start' => $value]);
})->with([1.5, 'not-an-integer'])->throws(InvalidOptionsException::class);

test('graph options reject invalid dimensions', function (mixed $value) {
	CactiRrdGraphOptions::resolve(['graph_width' => $value]);
})->with([NAN, 'not-a-number'])->throws(InvalidOptionsException::class);

test('graph option constraints reject invalid boundary values', function (array $options) {
	CactiRrdGraphOptions::resolve($options);
})->with([
	[['graph_height' => 0]],
	[['image_format' => 'gif']],
	[['output_flag' => 6]],
])->throws(InvalidOptionsException::class);
