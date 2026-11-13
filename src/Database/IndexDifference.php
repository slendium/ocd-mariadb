<?php

namespace Slendium\OcdMariaDb\Database;

use LogicException;
use Override;

use Slendium\OcdMariaDb\Common\Diff;
use Slendium\OcdMariaDb\Common\TableKey;

/**
 * @internal
 * @implements Diff\ObjectDiff<IndexDefinition>
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class IndexDifference implements Diff\ObjectDiff {

	#[Override]
	public static function diff(object $before, object $after): ?self {
		if ($before->identity !== $after->identity) {
			throw new LogicException("Expected indexes to have the same keys, not ({$before->identity}) and ({$after->identity})");
		}

		$construct = [ 'before' => $before, 'after' => $after ];

		if ($before->name !== $after->name) {
			$construct['name'] = new Diff\ChangedValue($before->name, $after->name);
		}

		if (!$before->key->equals($after->key)) {
			$construct['key'] = new Diff\ChangedValue($before->key, $after->key);
		}

		if ($before->isUnique !== $after->isUnique) {
			$construct['isUnique'] = new Diff\ChangedValue($before->isUnique, $after->isUnique);
		}

		return \count($construct) > 2
			? new self(...$construct)
			: null;
	}

	public function __construct(

		#[Override]
		public readonly IndexDefinition $before,

		#[Override]
		public readonly IndexDefinition $after,

		/** @var ?Diff<string> */
		public readonly ?Diff $name = null,

		/** @var ?Diff<TableKey> */
		public readonly ?Diff $key = null,

		/** @var ?Diff<bool> */
		public readonly ?Diff $isUnique = null,

	) { }

	/** @return iterable<non-empty-string> */
	public function toAlterTableClauses(): iterable {
		if ($this->name !== null && $this->key === null && $this->isUnique === null) {
			yield "RENAME INDEX `{$this->name->before}` TO `{$this->name->after}`";
			return;
		}

		yield $this->before->toAlterTableDropClause();
		yield $this->after->toAlterTableAddClause();
	}

}
