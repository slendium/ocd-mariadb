<?php

namespace Slendium\OcdMariaDb\Database;

use Override;
use RuntimeException;

use Slendium\Ocd\Schema\Constraint\Unique as UniqueConstraint;
use Slendium\Ocd\Schema\Index as SchemaIndex;

use Slendium\OcdMariaDb\Common\Diff;
use Slendium\OcdMariaDb\Common\TableKey;
use Slendium\OcdMariaDb\ShowSchema\Index as ShowIndex;
use Slendium\OcdMariaDb\ShowSchema\TableIndexes;
use Slendium\OcdMariaDb\ShowSchema\TableKeyFactory;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class IndexDefinition implements Diff\Identifiable {

	#[Override]
	public string $identity {
		get => $this->get_identity();
	}

	private string $type {
		get => $this->isUnique
			? 'UNIQUE INDEX'
			: 'INDEX';
	}

	/** @return list<self> */
	public static function fromTableIndexes(TableIndexes $table): array {
		$map = [ ];

		foreach ($table->indexes as $index) {
			if ($index->isPrimary) {
				continue;
			}

			if (!isset($map[$index->Key_name])) {
				$map[$index->Key_name] = [
					'indexes' => [ ],
					'isUnique' => $index->isUnique
				];
			}
			$map[$index->Key_name]['indexes'][] = $index;
			if ($index->isUnique !== $map[$index->Key_name]['isUnique']) {
				throw new RuntimeException('Expected all columns of the same key to have the same uniqueness');
			}
		}

		$result = [ ];
		foreach ($map as $name => $specs) {
			$result[] = self::fromIndexes($name, $specs['indexes'], $specs['isUnique']);
		}
		return $result;
	}

	public static function fromSchema(UniqueConstraint|SchemaIndex $index): self {
		$name = self::generateName($index->fields);
		$isUnique = false;
		if ($index instanceof UniqueConstraint) {
			$name = "uni_$name";
			$isUnique = true;
		}

		return new self($name, new TableKey($index->fields), $isUnique);
	}

	/** @param non-empty-list<ShowIndex> $indexes */
	private static function fromIndexes(string $name, array $indexes, bool $isUnique): self {
		return new self($name, TableKeyFactory::createFromIndexes($indexes), $isUnique);
	}

	/** @param non-empty-list<non-empty-string> $fields */
	private static function generateName(array $fields): string {
		return 'idx_'.\implode('_', $fields);
	}

	public function __construct(

		public readonly string $name,

		public readonly TableKey $key,

		public readonly bool $isUnique,

	) { }

	/** @return non-empty-string */
	public function toCreateTableClause(): string {
		return "{$this->type} `{$this->name}` ({$this->key})";
	}

	/** @return non-empty-string */
	public function toAlterTableDropClause(): string {
		return "DROP INDEX IF EXISTS `{$this->name}`";
	}

	/** @return non-empty-string */
	public function toAlterTableAddClause(): string {
		return "ADD {$this->type} IF NOT EXISTS `{$this->name}` ({$this->key})";
	}

	private function get_identity(): string {
		$columns = $this->key->columns;
		\sort($columns);
		return \implode(', ', $columns);
	}

}
