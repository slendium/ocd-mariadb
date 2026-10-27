<?php

namespace Slendium\OcdMariaDb\Database\DataType;

use Override;

use Slendium\OcdMariaDb\Database\DataType;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class Text implements DataType, Textual {

	const NAME = 'text';

	const LIMIT = 65_535;

	private static self $instance;

	#[Override]
	public int $characterLimit { get => self::LIMIT; }

	public static function instance(): self {
		return self::$instance ??= new self;
	}

	private function __construct() { }

	#[Override]
	public function toSql(): string {
		return self::NAME;
	}

	#[Override]
	public function fitsIn(DataType $newType): bool {
		return $newType instanceof Textual && $newType->characterLimit >= $this->characterLimit;
	}

}
