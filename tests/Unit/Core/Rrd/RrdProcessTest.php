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

require_once dirname(__DIR__, 4) . '/lib/CactiRrdProcess.php';

test('process runner executes argument arrays without a shell', function () {
	$result = (new CactiRrdProcessRunner())->run([PHP_BINARY, '-r', 'fwrite(STDOUT, "ok");']);

	expect($result->successful())->toBeTrue()
		->and($result->output)->toBe('ok')
		->and($result->errorOutput)->toBe('')
		->and($result->diagnosticOutput())->toBe('ok');
});

test('process runner supports existing shell command strings', function () {
	$command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg('fwrite(STDOUT, "shell");');
	$result  = (new CactiRrdProcessRunner())->run($command, 5.0);

	expect($result->successful())->toBeTrue()
		->and($result->output)->toBe('shell');
});

test('process runner captures stderr and nonzero exits', function () {
	$result = (new CactiRrdProcessRunner())->run([PHP_BINARY, '-r', 'fwrite(STDERR, "failed"); exit(7);']);

	expect($result->successful())->toBeFalse()
		->and($result->exitCode)->toBe(7)
		->and($result->diagnosticOutput())->toBe('failed');
});

test('process runner reports timeouts', function () {
	$result = (new CactiRrdProcessRunner())->run([PHP_BINARY, '-r', 'usleep(250000);'], 0.01);

	expect($result->successful())->toBeFalse()
		->and($result->timedOut)->toBeTrue()
		->and($result->exitCode)->toBe(-1)
		->and($result->diagnosticOutput())->not->toBe('');
});
