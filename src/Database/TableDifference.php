<?php

namespace Slendium\OcdMariaDb\Database;

use LogicException;
use Override;

use Slendium\OcdMariaDb\Common\Diff;
use Slendium\OcdMariaDb\Common\TableKey;

/**
 * @internal
 * @implements Diff\ObjectDiff<TableDefinition>
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class TableDifference implements Diff\ObjectDiff {

	/** @var non-empty-string */
	public string $name {
		get => $this->after->name;
	}

	#[Override]
	public static function diff(object $before, object $after): ?self {
		if ($before->name !== $after->name) {
			throw new LogicException("Expected tables to have the same name, not `{$before->name}` and `{$after->name}`");
		}

		$construct = [ 'before' => $before, 'after' => $after ];

		if ($before->primaryKey?->__toString() !== $after->primaryKey?->__toString()) {
			$construct['primaryKey'] = new Diff\ChangedValue($before->primaryKey, $after->primaryKey);
		}

		if ($before->autoIncrement !== $after->autoIncrement) {
			$construct['autoIncrement'] = new Diff\ChangedValue($before->autoIncrement, $after->autoIncrement);
		}

		$columnDiff = Diff\ListDifference::create($before->columns, $after->columns, ColumnDifference::class);
		if ($columnDiff !== null) {
			$construct['columns'] = $columnDiff;
		}

		return \count($construct) > 2
			? new self(...$construct)
			: null;
	}

	private function __construct(

		#[Override]
		public readonly TableDefinition $before,

		#[Override]
		public readonly TableDefinition $after,

		/** @var ?Diff<?TableKey> */
		public readonly ?Diff $primaryKey = null,

		/** @var ?Diff<?string> */
		public readonly ?Diff $autoIncrement = null,

		/** @var ?Diff\ListDifference<ColumnDefinition,ColumnDifference> */
		public readonly ?Diff\ListDifference $columns = null,

	) { }

}
