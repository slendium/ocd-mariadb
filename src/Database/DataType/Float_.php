<?php

namespace Slendium\OcdMariaDb\Database\DataType;

use Override;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class Float_ extends DecimalNumber {

	const NAME = 'float';

	const BYTES = 4;

	#[Override]
	protected string $name { get => self::NAME; }

	#[Override]
	protected int $bytes { get => self::BYTES; }

}
