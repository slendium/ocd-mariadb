<?php

namespace Slendium\OcdMariaDb\Database;

use Override;
use PDO;

use Slendium\Ocd\Database\UpgradeCommand;
use Slendium\Ocd\Database\UpgradeOptions;
use Slendium\Ocd\Schema;

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
		$before = TableDefinition::fromInfoSchema($this->pdo, $this->table);
		$after = TableDefinition::fromOcdSchema($this->schema, $this->table);

		if ($before === null && $after !== null) {
			$this->createTable($after);
		} else if ($before !== null && $after === null) {
			$this->dropTable();
		} else if ($after !== null) {
			$this->alterTable($before, $after);
		}
	}

	private function createTable(TableDefinition $definition): void {
		$this->pdo->query($definition->toCreateSql());
	}

	private function dropTable(): void {
		$this->pdo->query("DROP TABLE `{$this->table}`");
	}

	private function alterTable(TableDefinition $before, TableDefinition $after): void {
		$diff = TableDifference::diff($before, $after);
		if ($diff === null) {
			return;
		}

		foreach (AlterTableBuilder::createQueries($diff, $this->upgradeOptions) as $query) {
			$this->pdo->query($query);
		}
	}

}
