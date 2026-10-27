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
final readonly class ApplySchemaCommand implements UpgradeCommand {

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
		$columns = $this->fetchColumnInfo();

		if (\count($columns) > 0) {
			$this->alterTable($columns, $this->fetchJsonColumns());
		} else {
			$this->createTable();
		}
	}

	/**
	 * @param array<array{ COLUMN_NAME: string }> $existingColumns
	 * @param array<string,true> $jsonColumns
	 */
	private function alterTable(array $existingColumns, array $jsonColumns): void {
		$old = \array_map(function($columnRow) use ($jsonColumns) {
			$name = $columnRow['COLUMN_NAME'];
			return ColumnDefinition::fromInformationSchemaRow($columnRow, $jsonColumns[$name] ?? false);
		}, $existingColumns);
		$new = ColumnDefinition::createListFromSchema($this->schema);
		$diff = TableDifference::create($old, $new);

		$alterTable = (new AlterTableBuilder($this->table, $this->upgradeOptions));
		$alterTable->applyTableDifference($diff);

		foreach ($alterTable->toSql() as $query) {
			$this->pdo->query($query);
		}
	}

	private function createTable(): void {
		$columns = \array_map(
			fn($f) => ColumnDefinition::fromField($f, \in_array($f->name, $this->schema->primaries)),
			$this->schema->fields
		);

		$createDefinitions = $columns
			|> (fn($x) => \array_map(static fn($col) => $col->toCreateSql(), $x))
			|> (fn($x) => \implode(', ', $x));

		$this->pdo->query("CREATE TABLE `{$this->table}` ($createDefinitions)");
	}

	/** @return list<array{
	 * 		COLUMN_NAME: string,
	 * 		COLUMN_DEFAULT: mixed,
	 * 		IS_NULLABLE: 'YES'|'NO',
	 * 		DATA_TYPE: non-empty-string,
	 * 		COLUMN_KEY: 'PRI'|'UNI'|'MUL'|null,
	 * 		CHARACTER_MAXIMUM_LENGTH: ?int,
	 * }> */
	private function fetchColumnInfo(): array {
		$stmt = $this->pdo->prepare(
			'SELECT COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE, COLUMN_KEY, CHARACTER_MAXIMUM_LENGTH '
			.'FROM INFORMATION_SCHEMA.COLUMNS '
			.'WHERE TABLE_SCHEMA = SCHEMA() AND TABLE_NAME = :table'
		);

		$stmt->execute([ 'table' => $this->table ]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC); // @phpstan-ignore return.type
	}

	/** @return array<string,true> */
	private function fetchJsonColumns(): array {
		$stmt = $this->pdo->prepare(
			'SELECT CONSTRAINT_NAME, CHECK_CLAUSE'
			.' FROM INFORMATION_SCHEMA.CHECK_CONSTRAINTS'
			.' WHERE CONSTRAINT_SCHEMA = SCHEMA() AND TABLE_NAME = :table AND LEVEL = \'Column\''
		);
		$stmt->execute([ 'table' => $this->table ]);

		$out = [ ];
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			/** @var array{ CONSTRAINT_NAME: string, CHECK_CLAUSE: string } $row */
			if ($row['CHECK_CLAUSE'] === "json_valid(`{$row['CONSTRAINT_NAME']}`)") {
				$out[$row['CONSTRAINT_NAME']] = true;
			}
		}
		return $out;
	}

}
