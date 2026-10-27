<?php

namespace Slendium\OcdMariaDb\Database\DataType;

use LogicException;
use Override;

use Slendium\OcdMariaDb\Database\DataType;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
abstract class DecimalNumber implements DataType, Numeric {

	const MAX_DIGITS = 255;

	const MAX_DECIMALS = 30;

	/** @var non-empty-string */
	protected abstract string $name { get; }

	/** @var int<1,max> */
	protected abstract int $bytes { get; }

	public function __construct(

		/** @var ?int<0,self::MAX_DIGITS> */
		public readonly ?int $digits = null,

		/** @var int<0,self::MAX_DECIMALS> */
		public readonly ?int $decimals = null,

		#[Override]
		public readonly bool $isUnsigned = false,

	) {
		if ($decimals > $digits) {
			throw new LogicException("Expected amount of decimals ($decimals) to be less than or equal to amount of digits ($digits)'");
		}
	}

	#[Override]
	public function toSql(): string {
		$sql = $this->digits !== null && $this->decimals !== null
			? "{$this->name}({$this->digits}, {$this->decimals})"
			: $this->name;

		return $this->isUnsigned
			? "$sql unsigned"
			: $sql;
	}

	#[Override]
	public function fitsIn(DataType $newType): bool {
		if (!($newType instanceof self) || $this->bytes > $newType->bytes) {
			return false;
		}

		return $this->digits === null
			&& $newType->digits === null
			&& $this->decimals === null
			&& $newType->decimals === null
			&& $this->isUnsigned === $newType->isUnsigned
			|| \is_int($this->digits)
			&& \is_int($newType->digits)
			&& \is_int($this->decimals)
			&& \is_int($newType->decimals)
			&& $newType->digits >= $this->digits
			&& $newType->decimals >= $this->decimals
			&& $newType->isUnsigned === $this->isUnsigned;
	}

}
