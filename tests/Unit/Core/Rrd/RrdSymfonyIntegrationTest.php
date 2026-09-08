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

$root = dirname(__DIR__, 4);

test('RRD Symfony components are production dependencies', function () use ($root) {
	$composer = json_decode(file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);

	expect($composer['require'])
		->toHaveKey('symfony/filesystem', '^6.4')
		->toHaveKey('symfony/options-resolver', '^6.4')
		->toHaveKey('symfony/process', '^6.4')
		->toHaveKey('symfony/validator', '^6.4');
});

test('RRD graph boundaries resolve and validate options', function () use ($root) {
	$source = file_get_contents($root . '/lib/rrd.php');

	expect(substr_count($source, 'CactiRrdGraphOptions::resolve($graph_data_array)'))->toBe(2)
		->and($source)->toContain("require_once(__DIR__ . '/CactiRrdGraphOptions.php');")
		->and($source)->toContain("return 'ERROR: Invalid graph options';");
});

test('RRD one-shot execution uses Process and retains its persistent pipe', function () use ($root) {
	$source = file_get_contents($root . '/lib/rrd.php');

	expect($source)
		->toContain('(new CactiRrdProcessRunner())->run($rrd_cmd, 60.0)')
		->toContain('$processRunner->run($fullCommandline, 300.0)')
		->toContain('if (fwrite($rrdtool_pipe, " $command_line\r\n") === false) {')
		->not->toContain('shell_exec(')
		->not->toContain('$fp = popen($rrd_cmd');
});

test('RRD filesystem mutations use the Filesystem adapter', function () use ($root) {
	$source = file_get_contents($root . '/lib/rrd.php');

	expect($source)
		->toContain('$filesystem->createDirectory(')
		->toContain('$filesystem->changeOwnership(')
		->toContain('new CactiRrdFilesystem())->changeMode(')
		->toContain('$filesystem->move(')
		->toContain('$filesystem->remove(')
		->not->toMatch('/\b(?:mkdir|chown|chgrp|chmod|rename|unlink)\s*\(/');
});
