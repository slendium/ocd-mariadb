<?php

namespace Slendium\OcdMariaDb\Database\DataType;

use Override;

use Slendium\OcdMariaDb\Database\DataType;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class TinyInt extends WholeNumber {

	const NAME = 'tinyint';

	const BYTES = 1;

	const DEFAULT_DIGITS = 4;

	#[Override]
	protected string $name { get => self::NAME; }

	#[Override]
	protected int $bytes { get => self::BYTES; }

	/** @param int<self::MIN_DIGITS,self::MAX_DIGITS> $digits */
	public function __construct(int $digits = self::DEFAULT_DIGITS, bool $isUnsigned = false) {
		parent::__construct($digits, $isUnsigned);
	}

}
