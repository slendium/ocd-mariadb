<?php

namespace Slendium\OcdMariaDb\Database;

use Override;
use PDO;

use Slendium\Ocd\Database\UpgradeCommand;
use Slendium\Ocd\Database\UpgradeOptions;
use Slendium\Ocd\Schema;
use Slendium\Ocd\Schema\TypeInfo;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class EnforceSchemaCommand implements UpgradeCommand {

	#[Override]
	public UpgradeOptions $upgradeOptions;

	public function __construct(

		private PDO $pdo,

		/** @var non-empty-string */
		private string $table,

		private Schema $schema,

	) {
		$this->upgradeOptions = new UpgradeOptions;
	}

	#[Override]
	public function execute(): void {
		$stmt = $this->pdo->prepare(
			'SELECT COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE, COLUMN_KEY, CHARACTER_MAXIMUM_LENGTH '
			.'FROM information_schema.columns '
			.'WHERE TABLE_SCHEMA = database() AND TABLE_NAME = :table'
		);

		$stmt->execute([ 'table' => $this->table ]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (\count($rows) > 0) {
			$this->alterTable($rows); // @phpstan-ignore argument.type
		} else {
			$this->createTable();
		}
	}

	/** @param array<array<mixed>> $existingColumns */
	private function alterTable(array $existingColumns): void {
		$old = \array_map(ColumnDefinition::fromInformationSchemaRow(...), $existingColumns);
		$new = ColumnDefinition::createListFromSchema($this->schema);
		$diff = TableDifference::create($old, $new);

		$alterTable = (new AlterTableBuilder($this->table, $this->upgradeOptions));
		$alterTable->applyTableDifference($diff);

		foreach ($alterTable->toSql() as $query) {
			$this->pdo->query($query);
		}
	}

	private function createTable(): void {
		$columns = [
			ColumnDefinition::fromIdOptions($this->schema->idOptions),
			...\array_map(ColumnDefinition::fromField(...), [ ...$this->schema->fields ])
		];

		$createDefinitions = $columns
			|> (fn($x) => \array_map(static fn($col) => $col->toCreateSql(), $x))
			|> (fn($x) => \implode(', ', $x));

		$this->pdo->query("CREATE TABLE {$this->table} ($createDefinitions)");
	}

}
