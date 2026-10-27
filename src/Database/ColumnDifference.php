<?php

namespace Slendium\OcdMariaDb\Database;

use LogicException;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class ColumnDifference {

	public string $name {
		get => $this->columnAfter->name;
	}

	public bool $isTypeChange {
		get => $this->type !== null && $this->type->before::class !== $this->type->after::class;
	}

	public bool $isDataLoss {
		get => $this->type !== null && !$this->type->before->fitsIn($this->type->after);
	}

	public static function tryCreate(ColumnDefinition $before, ColumnDefinition $after): ?self {
		if ($before->name !== $after->name) {
			throw new LogicException('Expected names of old and new column definition to be the same');
		}

		$construct = [ ];

		if ($before->type->toSql() !== $after->type->toSql()) {
			$construct['type'] = new Change($before->type, $after->type);
		}

		if ($before->isNullable !== $after->isNullable) {
			$construct['isNullable'] = new Change($before->isNullable, $after->isNullable);
		}

		if ($before->defaultValue !== $after->defaultValue) {
			$construct['defaultValue'] = new Change($before->defaultValue, $after->defaultValue);
		}

		if ($before->isUnsigned !== $after->isUnsigned) {
			$construct['isUnsigned'] = new Change($before->isUnsigned, $after->isUnsigned);
		}

		if ($before->isPrimaryKey !== $after->isPrimaryKey) {
			$construct['isPrimaryKey'] = new Change($before->isPrimaryKey, $after->isPrimaryKey);
		}

		if ($before->isAutoIncrement !== $after->isAutoIncrement) {
			$construct['isAutoIncrement'] = new Change($before->isAutoIncrement, $after->isAutoIncrement);
		}

		if ($before->isJson !== $after->isJson) {
			$construct['isJson'] = new Change($before->isJson, $after->isJson);
		}

		return \count($construct) > 0
			? new self(...[ 'columnBefore' => $before, 'columnAfter' => $after, ...$construct ])
			: null;
	}

	public function __construct(

		public readonly ColumnDefinition $columnBefore,

		public readonly ColumnDefinition $columnAfter,

		/** @var ?Change<DataType> */
		public readonly ?Change $type = null,

		/** @var ?Change<bool> */
		public readonly ?Change $isNullable = null,

		/** @var ?Change<mixed> */
		public readonly ?Change $defaultValue = null,

		/** @var ?Change<bool> */
		public readonly ?Change $isUnsigned = null,

		/** @var ?Change<bool> */
		public readonly ?Change $isPrimaryKey = null,

		/** @var ?Change<bool> */
		public readonly ?Change $isAutoIncrement = null,

		/** @var ?Change<bool> */
		public readonly ?Change $isJson = null,

	) { }

}
