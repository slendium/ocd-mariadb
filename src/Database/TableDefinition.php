<?php

namespace Slendium\OcdMariaDb\Database;

use LogicException;
use PDO;
use RuntimeException;

use Slendium\Ocd\Schema;

use Slendium\OcdMariaDb\Common\TableKey;
use Slendium\OcdMariaDb\InfoSchema\CheckConstraint;
use Slendium\OcdMariaDb\InfoSchema\Column;
use Slendium\OcdMariaDb\ShowSchema\TableIndexes;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class TableDefinition {

	/** @param non-empty-string $name */
	public static function fromInfoSchema(PDO $pdo, string $name): ?self {
		$jsonColumns = [ ];
		foreach (CheckConstraint::fetchForTable($pdo, $name) as $check) {
			if ($check->isJsonColumn) {
				$jsonColumns[$check->CONSTRAINT_NAME] = true;
			}
		}

		$columnDefinitions = [ ];
		$autoIncrement = null;
		foreach (Column::fetchForTable($pdo, $name) as $column) {
			$columnDefinitions[] = ColumnDefinition::fromInfoSchema(
				$column,
				isJson: $jsonColumns[$column->COLUMN_NAME] ?? false
			);

			if ($column->isAutoIncrement) {
				$autoIncrement = $autoIncrement === null
					? $column->COLUMN_NAME
					: throw new RuntimeException("Expected only one auto_increment column, found `$autoIncrement` and `{$column->COLUMN_NAME}`");
			}
		}

		return \count($columnDefinitions) > 0
			? new self($name, TableIndexes::fetch($pdo, $name)->primaryKey, $autoIncrement, $columnDefinitions)
			: null;
	}

	/** @param non-empty-string $name */
	public static function fromOcdSchema(Schema $schema, string $name): ?self {
		$columnDefinitions = [ ];
		$autoIncrement = null;

		foreach ($schema->fields as $field) {
			$columnDefinition = ColumnDefinition::fromField($field);
			$columnDefinitions[] = $columnDefinition;

			if ($columnDefinition->isAutoIncrement) {
				$autoIncrement = $autoIncrement === null
					? $columnDefinition->name
					: throw new LogicException("Expected at most one SequentialValue field, found `$autoIncrement` and `{$columnDefinition->name}`");
			}
		}

		if (\count($columnDefinitions) < 1) {
			return null;
		}

		$primaryKey = \count($schema->primaries) > 0
			? new TableKey(\array_map(fn($f) => $f->name, $schema->primaries))
			: null;
		return new self($name, $primaryKey, $autoIncrement, $columnDefinitions);
	}

	public function __construct(

		/** @var non-empty-string */
		public string $name,

		public ?TableKey $primaryKey,

		public ?string $autoIncrement,

		/** @var non-empty-list<ColumnDefinition> */
		public array $columns,

	) { }

	public function toCreateSql(): string {
		$definitions = \array_map(fn($col) => $col->toCreateSql(), $this->columns);
		if ($this->primaryKey !== null) {
			$definitions[] = "PRIMARY KEY ({$this->primaryKey})";
		}

		$definitions = \implode(', ', $definitions);
		return "CREATE TABLE `{$this->name}` ($definitions)";
	}

}
