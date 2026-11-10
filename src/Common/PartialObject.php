<?php

namespace Slendium\OcdMariaDb\Common;

use ReflectionClass;
use RuntimeException;

/**
 * Utility for constructing partial objects.
 *
 * @internal
 * @author C. Fahner
 * @copyright Sledium 2026
 */
final class PartialObject {

	/**
	 * Creates a potentially partial object from a row, initializing only the properties for which
	 * matching keys in the row can be found.
	 *
	 * @template T of object
	 * @param class-string<T> $class
	 * @param array<mixed> $row
	 * @return T
	 */
	public static function createFromRow(string $class, array $row): object {
		$reflector = new ReflectionClass($class);
		$ghost = $reflector->newLazyGhost(function($obj) {
			throw new RuntimeException('Unexpected initialization of partial object');
		});

		foreach ($row as $name => $value) {
			if ($reflector->hasProperty($name)) {
				$reflector->getProperty($name)->setRawValueWithoutLazyInitialization($ghost, $value);
			}
		}

		return $ghost;
	}

	private function __construct() { }

}
