<?php

namespace Slendium\OcdMariaDb\Collection;

use ArrayAccess;
use Countable;
use Traversable;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class InsertQuery {

	/**
	 * @param non-empty-string $table
	 * @param ArrayAccess<non-empty-string,mixed>&Countable&Traversable<non-empty-string,mixed> $document
	 */
	public static function create(string $table, ArrayAccess&Countable&Traversable $document): self {
		$counter = 0;
		$columnOrder = [ ];
		$parameterOrder = [ ];
		$parameters = [ ];

		foreach ($document as $field => $value) {
			$parameter = 'p'.($counter += 1);
			$columnOrder[] = $field;
			$parameterOrder[] = $parameter;
			$parameters[$parameter] = \is_array($value)
				? \json_encode($value)
				: $value;
		}

		$columnListing = $columnOrder
			|> (fn($x) => \array_map(static fn($column) => "`$column`", $x))
			|> (fn($x) => \implode(', ', $x));
		$parameterListing = $parameterOrder
			|> (fn($x) => \array_map(static fn($param) => ":$param", $x))
			|> (fn($x) => \implode(', ', $x));

		return new self("INSERT INTO `$table` ($columnListing) VALUES ($parameterListing)", $parameters);
	}

	private function __construct(

		/** @var non-empty-string */
		public string $query,

		/** @var array<non-empty-string,mixed> */
		public array $parameters,

	) { }

}
