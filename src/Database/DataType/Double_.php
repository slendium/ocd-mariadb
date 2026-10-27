<?php

namespace Slendium\OcdMariaDb\Database\DataType;

use Override;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class Double_ extends DecimalNumber {

	const NAME = 'double';

	const BYTES = 8;

	#[Override]
	protected string $name { get => self::NAME; }

	#[Override]
	protected int $bytes { get => self::BYTES; }

}
