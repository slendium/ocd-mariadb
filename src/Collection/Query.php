<?php

namespace Slendium\OcdMariaDb\Collection;

use ArrayAccess;
use Countable;
use Traversable;

use Slendium\Ocd\Query\Predicate;
use Slendium\Ocd\Query\Update;

use Slendium\OcdMariaDb\Query\PredicateSqlizer;
use Slendium\OcdMariaDb\Query\UpdateSqlizer;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class Query {

	/**
	 * @param non-empty-string $table
	 * @param ArrayAccess<non-empty-string,mixed>&Countable&Traversable<non-empty-string,mixed> $document
	 */
	public static function insert(string $table, ArrayAccess&Countable&Traversable $document): self {
		$counter = 0;
		$columnOrder = [ ];
		$argumentOrder = [ ];
		$arguments = [ ];

		foreach ($document as $field => $value) {
			$argName = 'v'.($counter += 1);
			$columnOrder[] = $field;
			$argumentOrder[] = $argName;
			$arguments[$argName] = \is_array($value)
				? \json_encode($value)
				: $value;
		}

		$columnListing = $columnOrder
			|> (fn($x) => \array_map(static fn($column) => "`$column`", $x))
			|> (fn($x) => \implode(', ', $x));
		$argumentListing = $argumentOrder
			|> (fn($x) => \array_map(static fn($argName) => ":$argName", $x))
			|> (fn($x) => \implode(', ', $x));

		return new self("INSERT INTO `$table` ($columnListing) VALUES ($argumentListing)", $arguments);
	}

	/**
	 * @param non-empty-string $table
	 * @param non-empty-list<Update> $updates
	 */
	public static function update(string $table, array $updates, Predicate $predicate): self {
		[ $setClause, $setArgs ] = (new UpdateSqlizer)->sqlize($updates);
		[ $whereClause, $whereArgs ] = new PredicateSqlizer($table)->sqlize($predicate);

		return new self("UPDATE `$table` SET $setClause WHERE $whereClause", [ ...$setArgs, ...$whereArgs ]);
	}

	private function __construct(

		/** @var non-empty-string */
		public string $sql,

		/** @var array<non-empty-string,mixed> */
		public array $arguments,

	) { }

}
