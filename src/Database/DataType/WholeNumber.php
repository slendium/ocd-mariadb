<?php

namespace Slendium\OcdMariaDb\Database\DataType;

use Override;

use Slendium\OcdMariaDb\Database\DataType;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
abstract class WholeNumber implements DataType, Numeric {

	const MIN_DIGITS = 1;

	const MAX_DIGITS = 255;

	/** @var non-empty-string */
	protected abstract string $name { get; }

	/** @var int<1,max> */
	protected abstract int $bytes { get; }

	public function __construct(

		/** @var int<self::MIN_DIGITS,self::MAX_DIGITS> */
		public readonly int $digits,

		#[Override]
		public readonly bool $isUnsigned = false,

	) { }

	#[Override]
	public function toSql(): string {
		return $this->isUnsigned
			? "{$this->name}({$this->digits}) unsigned"
			: "{$this->name}({$this->digits})";
	}

	#[Override]
	public function fitsIn(DataType $newType): bool {
		if (!($newType instanceof self)) {
			return false;
		}

		return $newType->bytes >= $this->bytes
			&& $newType->digits >= $this->digits
			&& $newType->isUnsigned === $this->isUnsigned;
	}

}
