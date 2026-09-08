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

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

final class CactiRrdFilesystem {
	private ?IOExceptionInterface $lastError = null;

	public function __construct(private readonly Filesystem $filesystem = new Filesystem()) {
	}

	public function createDirectory(string $path, int $mode = 0775): bool {
		return $this->attempt(fn () => $this->filesystem->mkdir($path, $mode));
	}

	public function changeOwnership(string $path, string|int|null $owner, string|int|null $group): bool {
		return $this->attempt(function () use ($path, $owner, $group): void {
			if ($owner !== null) {
				$this->filesystem->chown($path, $owner);
			}

			if ($group !== null) {
				$this->filesystem->chgrp($path, $group);
			}
		});
	}

	public function changeMode(string $path, int $mode): bool {
		return $this->attempt(fn () => $this->filesystem->chmod($path, $mode));
	}

	public function move(string $source, string $target, bool $overwrite = false): bool {
		return $this->attempt(fn () => $this->filesystem->rename($source, $target, $overwrite));
	}

	public function remove(string|array $paths): bool {
		return $this->attempt(fn () => $this->filesystem->remove($paths));
	}

	public function lastError(): ?IOExceptionInterface {
		return $this->lastError;
	}

	private function attempt(callable $operation): bool {
		$this->lastError = null;

		try {
			$operation();

			return true;
		} catch (IOExceptionInterface $exception) {
			$this->lastError = $exception;

			return false;
		}
	}
}
