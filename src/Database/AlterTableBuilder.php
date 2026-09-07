<?php

namespace Slendium\OcdMariaDb\Database;

use Exception;

use Slendium\Ocd\Database\UpgradeOptions;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class AlterTableBuilder {

	/** @var list<non-empty-string> */
	private array $addColumns = [ ];

	/** @var list<non-empty-string> */
	private array $truncateColumns = [ ];

	/** @var list<non-empty-string> */
	private array $modifyColumns = [ ];

	/** @var list<non-empty-string> */
	private array $dropColumns = [ ];

	/** @var ?Change<?non-empty-string> */
	private ?Change $primaryKeyChange = null;

	public function __construct(

		private string $table,

		private UpgradeOptions $upgradeOptions,

	) { }

	public function applyTableDifference(TableDifference $diff): void {
		foreach ($diff->additions as $addition) {
			$this->addColumns[] = $addition->toAddColumnSql();
		}

		foreach ($diff->differences as $columnDiff) {
			$this->alterColumn($columnDiff);
		}

		if ($this->upgradeOptions->allowDrop) {
			foreach ($diff->drops as $columnName) {
				$this->dropColumns[] = "DROP COLUMN `$columnName`";
			}
		}

		$this->primaryKeyChange = $diff->primaryKeyChange;
	}

	/**
	 * Returns all SQL queries that need to be executed (in the given order) for the table alteration.
	 * @return iterable<non-empty-string>
	 */
	public function toSql(): iterable {
		yield from $this->truncateColumns;

		$alterSpecs = [ ...$this->addColumns, ...$this->modifyColumns, ...$this->dropColumns ];

		if ($this->primaryKeyChange !== null) {
			$alterSpecs[] = 'DROP PRIMARY KEY';
			if ($this->primaryKeyChange->after !== null) {
				$alterSpecs[] = "ADD PRIMARY KEY (`{$this->primaryKeyChange->after}`)";
			}
		}

		yield "ALTER TABLE {$this->table} ".\implode(', ', $alterSpecs);
	}

	private function alterColumn(ColumnDifference $difference): void {
		if ($difference->isTypeChange) {
			throw new Exception('Field type changes have not yet been implemented');
		}

		if ($difference->isTruncation) {
			$this->truncateColumn($difference);
		} else {
			$this->modifyColumns[] = $difference->columnAfter->toModifySql();
		}
	}

	private function truncateColumn(ColumnDifference $diff): void {
		if ($this->upgradeOptions->allowTruncate) {
			$chars = $diff->columnAfter->characterCount ?? 255;
			$this->truncateColumns[] = "UPDATE {$this->table} SET `{$diff->name}` = LEFT(`{$diff->name}`, {$chars})";
			$this->modifyColumns[] = $diff->columnAfter->toModifySql();
		} else {
			$this->modifyColumns[] = $diff->columnAfter->toModifySql($diff->columnBefore->characterCount);
		}
	}

}
