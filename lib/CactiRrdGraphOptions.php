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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class CactiRrdGraphOptions {
	private const FLAG_OPTIONS = [
		'export',
		'export_csv',
		'get_error',
		'graph_nolegend',
		'graphv',
		'print_source',
	];

	private const PATH_OPTIONS = [
		'export_filename',
		'export_realtime',
		'output_filename',
	];

	/**
	 * Resolve the options understood by lib/rrd.php while retaining extension
	 * options consumed by plugin hooks.
	 */
	public static function resolve(array $options): array {
		$resolver = new OptionsResolver();
		$known    = array_merge(self::FLAG_OPTIONS, self::PATH_OPTIONS, [
			'graph_end',
			'graph_height',
			'graph_start',
			'graph_theme',
			'graph_width',
			'image_format',
			'output_flag',
		]);

		$resolver->setDefined($known);

		foreach (self::FLAG_OPTIONS as $name) {
			$resolver->setAllowedTypes($name, ['bool', 'int', 'string']);
		}

		foreach (self::PATH_OPTIONS as $name) {
			$resolver->setAllowedTypes($name, 'string');
		}

		foreach (['graph_start', 'graph_end', 'graph_height', 'graph_width', 'output_flag'] as $name) {
			$resolver->setAllowedTypes($name, ['int', 'float', 'string']);
		}

		foreach (['graph_theme', 'image_format'] as $name) {
			$resolver->setAllowedTypes($name, 'string');
		}

		foreach (['graph_start', 'graph_end', 'output_flag'] as $name) {
			$resolver->setNormalizer($name, static fn (Options $unused, mixed $value): int => self::normalizeInteger($name, $value));
		}

		foreach (['graph_height', 'graph_width'] as $name) {
			$resolver->setNormalizer($name, static fn (Options $unused, mixed $value): int|float => self::normalizeNumber($name, $value));
		}

		$knownOptions = array_intersect_key($options, array_flip($known));
		$resolved     = $resolver->resolve($knownOptions);

		self::validate($resolved);

		return array_replace($options, $resolved);
	}

	private static function normalizeInteger(string $name, mixed $value): int {
		if (is_int($value)) {
			return $value;
		}

		if ((is_string($value) || is_float($value)) && filter_var($value, FILTER_VALIDATE_INT) !== false) {
			return (int) $value;
		}

		throw new InvalidOptionsException("The '$name' option must be an integer.");
	}

	private static function normalizeNumber(string $name, mixed $value): int|float {
		if ((is_int($value) || is_float($value)) && is_finite((float) $value)) {
			return $value;
		}

		if (is_string($value) && is_numeric($value) && is_finite((float) $value)) {
			return str_contains($value, '.') ? (float) $value : (int) $value;
		}

		throw new InvalidOptionsException("The '$name' option must be a finite number.");
	}

	private static function validate(array $options): void {
		$constraints = [];

		foreach (['graph_height', 'graph_width'] as $name) {
			if (array_key_exists($name, $options)) {
				$constraints[$name] = new Assert\Positive();
			}
		}

		if (array_key_exists('image_format', $options)) {
			$constraints['image_format'] = new Assert\Choice(['png', 'svg+xml']);
		}

		if (array_key_exists('output_flag', $options)) {
			$constraints['output_flag'] = new Assert\Choice([0, 1, 2, 3, 4, 5]);
		}

		if ($constraints === []) {
			return;
		}

		$violations = Validation::createValidator()->validate(
			$options,
			new Assert\Collection(fields: $constraints, allowExtraFields: true, allowMissingFields: true)
		);

		if ($violations->count() > 0) {
			$messages = [];

			foreach ($violations as $violation) {
				$messages[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
			}

			throw new InvalidOptionsException(implode('; ', $messages));
		}
	}
}
