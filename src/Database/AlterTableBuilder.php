<?php

namespace Slendium\OcdMariaDb\Database;

use Slendium\Ocd\Database\UpgradeOptions;

use Slendium\OcdMariaDb\Common\Diff;
use Slendium\OcdMariaDb\Common\TableKey;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class AlterTableBuilder {

	/** @var list<non-empty-string> */
	private array $alterations = [ ];

	/** @var list<non-empty-string> */
	private array $truncations = [ ];

	/** @return iterable<non-empty-string> */
	public static function createQueries(TableDifference $diff, UpgradeOptions $options): iterable {
		$builder = new self($options);

		if ($diff->primaryKey !== null) {
			$builder->addPrimaryKey($diff->primaryKey);
		}

		if ($diff->columns !== null) {
			$builder->applyColumnsDiff($diff->name, $diff->columns);
		}

		if ($diff->indexes !== null) {
			$builder->applyIndexesDiff($diff->indexes);
		}

		yield from $builder->truncations;
		yield "ALTER TABLE `{$diff->name}` ".\implode(', ', $builder->alterations);
	}

	private function __construct(private UpgradeOptions $options) { }

	/** @param Diff<?TableKey> $diff */
	private function addPrimaryKey(Diff $diff): void {
		if ($diff->before !== null) {
			$this->alterations[] = 'DROP PRIMARY KEY';
		}

		if ($diff->after !== null) {
			$this->alterations[] = "ADD PRIMARY KEY ({$diff->after})";
		}
	}

	/** @param Diff\ListDifference<ColumnDefinition,ColumnDifference> $diff */
	private function applyColumnsDiff(string $tableName, Diff\ListDifference $diff): void {
		foreach ($diff->additions as $add) {
			$this->addColumn($add);
		}
		foreach ($diff->modifications as $columnDiff) {
			$this->alterColumn($tableName, $columnDiff);
		}
		foreach ($diff->deletions as $column) {
			$this->dropColumn($column->name);
		}
	}

	/** @param Diff\ListAddition<ColumnDefinition> $addition */
	private function addColumn(Diff\ListAddition $addition): void {
		$alter = "ADD COLUMN {$addition->item->toCreateSql()}";
		$alter .= $addition->after !== null
			? " AFTER `{$addition->after}`"
			: ' FIRST';

		$this->alterations[] = $alter;
	}

	private function alterColumn(string $table, ColumnDifference $diff): void {
		if ($diff->isTypeChange) {
			throw new \Exception('Not implemented');
		}

		if (!$diff->isDataLoss) {
			$this->alterations[] = $diff->after->toModifySql();
			return;
		}

		if (!$this->options->allowLoss) {
			return;
		}

		if ($diff->after->type instanceof DataType\Textual) {
			$chars = $diff->after->type->characterLimit;
			$this->truncations[] = "UPDATE `$table` SET `{$diff->name}` = LEFT(`{$diff->name}`, {$chars})";
		}
		$this->alterations[] = $diff->after->toModifySql();
	}

	private function dropColumn(string $name): void {
		if ($this->options->allowLoss) {
			$this->alterations[] = "DROP COLUMN `$name`";
		}
	}

	/** @param Diff\ListDifference<IndexDefinition,IndexDifference> $diff */
	private function applyIndexesDiff(Diff\ListDifference $diff): void {
		foreach ($diff->additions as $add) {
			$this->alterations[] = $add->item->toAlterTableAddClause();
		}
		foreach ($diff->modifications as $modif) {
			foreach ($modif->toAlterTableClauses() as $clause) {
				$this->alterations[] = $clause;
			}
		}
		foreach ($diff->deletions as $deletion) {
			$this->alterations[] = $deletion->toAlterTableDropClause();
		}
	}

}
