<?php

namespace Slendium\OcdMariaDb\Database;

use Slendium\Ocd\Schema\DefinitionException;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class TableDifference {

	/**
	 * @param iterable<ColumnDefinition> $source
	 * @param iterable<ColumnDefinition> $target
	 */
	public static function create(iterable $source, iterable $target): self {
		$sourceMap = [ ];
		$drops = [ ];
		$oldPrimaryKey = null;
		$newPrimaryKey = null;

		foreach ($source as $col) {
			$sourceMap[$col->name] = $col;
			$drops[$col->name] = true;
			if ($col->isPrimaryKey) {
				$oldPrimaryKey = $col->name;
			}
		}

		$additions = [ ];
		$differences = [ ];

		$previousName = null;
		foreach ($target as $col) {
			if (isset($sourceMap[$col->name])) {
				unset($drops[$col->name]);
				$diff = ColumnDifference::tryCreate($sourceMap[$col->name], $col);
				if ($diff !== null) {
					$differences[] = $diff;
				}
			} else {
				$additions[] = new ColumnAddition($col, $previousName);
			}
			$previousName = $col->name;

			if ($col->isPrimaryKey) {
				$newPrimaryKey = $newPrimaryKey === null
					? $col->name
					: throw new DefinitionException('Expected only one primary key in the new collection definition');
			}
		}

		$primaryKeyChange = $oldPrimaryKey !== $newPrimaryKey
			? new Change($oldPrimaryKey, $newPrimaryKey)
			: null;

		return new self($additions, $differences, \array_keys($drops), $primaryKeyChange);
	}

	private function __construct(

		/** @var list<ColumnAddition> */
		public array $additions,

		/** @var list<ColumnDifference> */
		public array $differences,

		/** @var list<non-empty-string> */
		public array $drops,

		/** @var ?Change<?non-empty-string> */
		public ?Change $primaryKeyChange = null,

	) { }

}
