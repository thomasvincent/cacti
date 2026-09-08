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

require_once dirname(__DIR__, 4) . '/lib/CactiRrdFilesystem.php';

test('filesystem adapter performs RRD mutations', function () {
	$root        = sys_get_temp_dir() . '/cacti-rrd-filesystem-' . bin2hex(random_bytes(8));
	$source      = $root . '/source.rrd';
	$destination = $root . '/destination.rrd';
	$filesystem  = new CactiRrdFilesystem();

	expect($filesystem->createDirectory($root, 0755))->toBeTrue();
	file_put_contents($source, 'rrd');

	expect($filesystem->changeMode($source, 0644))->toBeTrue()
		->and($filesystem->changeOwnership($source, null, null))->toBeTrue();

	if (function_exists('posix_getuid')) {
		expect($filesystem->changeOwnership($source, posix_getuid(), null))->toBeTrue()
			->and($filesystem->changeOwnership($source, null, posix_getgid()))->toBeTrue();
	}

	expect($filesystem->move($source, $destination, true))->toBeTrue()
		->and(file_exists($destination))->toBeTrue()
		->and($filesystem->remove([$destination, $root]))->toBeTrue()
		->and(file_exists($root))->toBeFalse()
		->and($filesystem->lastError())->toBeNull();
});

test('filesystem adapter reports IO failures and clears stale errors', function () {
	$root       = sys_get_temp_dir() . '/cacti-rrd-filesystem-' . bin2hex(random_bytes(8));
	$file       = $root . '/file';
	$filesystem = new CactiRrdFilesystem();

	$filesystem->createDirectory($root);
	file_put_contents($file, 'not a directory');

	expect($filesystem->createDirectory($file . '/child'))->toBeFalse()
		->and($filesystem->lastError())->not->toBeNull()
		->and($filesystem->remove($root))->toBeTrue()
		->and($filesystem->lastError())->toBeNull();
});
