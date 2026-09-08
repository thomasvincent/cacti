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

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

final class CactiRrdProcessResult {
	public function __construct(
		public readonly int $exitCode,
		public readonly string $output,
		public readonly string $errorOutput,
		public readonly bool $timedOut = false
	) {
	}

	public function successful(): bool {
		return !$this->timedOut && $this->exitCode === 0;
	}

	public function diagnosticOutput(): string {
		return $this->errorOutput !== '' ? $this->errorOutput : $this->output;
	}
}

final class CactiRrdProcessRunner {
	public function run(string|array $command, ?float $timeout = null): CactiRrdProcessResult {
		$process = is_array($command)
			? new Process($command)
			: Process::fromShellCommandline($command);

		$process->setTimeout($timeout);

		try {
			$exitCode = $process->run();
		} catch (ProcessTimedOutException $exception) {
			return new CactiRrdProcessResult(-1, $process->getOutput(), $exception->getMessage(), true);
		}

		return new CactiRrdProcessResult($exitCode, $process->getOutput(), $process->getErrorOutput());
	}
}
