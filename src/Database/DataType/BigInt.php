<?php

namespace Slendium\OcdMariaDb\Database\DataType;

use Override;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class BigInt extends WholeNumber {

	const NAME = 'bigint';

	const BYTES = 8;

	const DEFAULT_DIGITS = 20;

	#[Override]
	protected string $name { get => self::NAME; }

	#[Override]
	protected int $bytes { get => self::BYTES; }

	/** @param int<self::MIN_DIGITS,self::MAX_DIGITS> $digits */
	public function __construct(int $digits = self::DEFAULT_DIGITS, bool $isUnsigned = false) {
		parent::__construct($digits, $isUnsigned);
	}

}
