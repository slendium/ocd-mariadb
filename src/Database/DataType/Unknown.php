<?php

namespace Slendium\OcdMariaDb\Database\DataType;

use Override;

use Slendium\OcdMariaDb\Database\DataType;

/**
 * Placeholder to preserve unknown types.
 *
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class Unknown implements DataType {

	public function __construct(

		/** @var non-empty-string */
		public string $type,

	) { }

	#[Override]
	public function toSql(): string {
		return $this->type;
	}

	#[Override]
	public function fitsIn(DataType $newType): bool {
		return $newType->toSql() === $this->toSql();
	}

}
