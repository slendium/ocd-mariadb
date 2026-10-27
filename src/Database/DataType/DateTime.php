<?php

namespace Slendium\OcdMariaDb\Database\DataType;

use Override;

use Slendium\OcdMariaDb\Database\DataType;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class DateTime implements DataType {

	const NAME = 'datetime';

	public function __construct(

		/** @var int<0,6> */
		public int $msPrecision = 0,

	) { }

	#[Override]
	public function toSql(): string {
		return $this->msPrecision > 0
			? self::NAME."({$this->msPrecision})"
			: self::NAME;
	}

	#[Override]
	public function fitsIn(DataType $newType): bool {
		return $newType instanceof self && $newType->msPrecision >= $this->msPrecision;
	}

}
