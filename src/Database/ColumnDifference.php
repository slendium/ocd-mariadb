<?php

namespace Slendium\OcdMariaDb\Database;

use LogicException;
use Override;

use Slendium\OcdMariaDb\Common\Diff;

/**
 * @internal
 * @implements Diff\ObjectDiff<ColumnDefinition>
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class ColumnDifference implements Diff\ObjectDiff {

	public string $name {
		get => $this->after->name;
	}

	public bool $isTypeChange {
		get => $this->type !== null && $this->type->before::class !== $this->type->after::class;
	}

	public bool $isDataLoss {
		get => $this->type !== null && !$this->type->before->fitsIn($this->type->after);
	}

	#[Override]
	public static function diff(object $before, object $after): ?self {
		if ($before->name !== $after->name) {
			throw new LogicException("Expected columns to have the same name, not `{$before->name}` and `{$after->name}`");
		}

		$construct = [ 'before' => $before, 'after' => $after ];

		if ($before->type->toSql() !== $after->type->toSql()) {
			$construct['type'] = new Diff\ChangedValue($before->type, $after->type);
		}

		if ($before->isNullable !== $after->isNullable) {
			$construct['isNullable'] = new Diff\ChangedValue($before->isNullable, $after->isNullable);
		}

		if ($before->defaultValue !== $after->defaultValue) {
			$construct['defaultValue'] = new Diff\ChangedValue($before->defaultValue, $after->defaultValue);
		}

		if ($before->isUnsigned !== $after->isUnsigned) {
			$construct['isUnsigned'] = new Diff\ChangedValue($before->isUnsigned, $after->isUnsigned);
		}

		if ($before->isAutoIncrement !== $after->isAutoIncrement) {
			$construct['isAutoIncrement'] = new Diff\ChangedValue($before->isAutoIncrement, $after->isAutoIncrement);
		}

		if ($before->isJson !== $after->isJson) {
			$construct['isJson'] = new Diff\ChangedValue($before->isJson, $after->isJson);
		}

		return \count($construct) > 2
			? new self(...$construct)
			: null;
	}

	public function __construct(

		#[Override]
		public readonly ColumnDefinition $before,

		#[Override]
		public readonly ColumnDefinition $after,

		/** @var ?Diff<DataType> */
		public readonly ?Diff $type = null,

		/** @var ?Diff<bool> */
		public readonly ?Diff $isNullable = null,

		/** @var ?Diff<mixed> */
		public readonly ?Diff $defaultValue = null,

		/** @var ?Diff<bool> */
		public readonly ?Diff $isUnsigned = null,

		/** @var ?Diff<bool> */
		public readonly ?Diff $isAutoIncrement = null,

		/** @var ?Diff<bool> */
		public readonly ?Diff $isJson = null,

	) { }

}
