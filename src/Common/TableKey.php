<?php

namespace Slendium\OcdMariaDb\Common;

/**
 * An ordered list of columns which create a key together.
 *
 * The order of columns is important.
 *
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class TableKey {

	public function __construct(

		/** @var non-empty-list<string> */
		public array $columns,

	) { }

	public function equals(self $other): bool {
		foreach ($this->columns as $i => $name) {
			if (!isset($other->columns[$i]) || $other->columns[$i] !== $name) {
				return false;
			}
		}
		return true;
	}

	/** @return non-empty-string */
	public function __toString(): string {
		return $this->columns
			|> (fn($x) => \array_map(fn($name) => "`$name`", $x))
			|> (fn($x) => \implode(', ', $x));
	}

}
