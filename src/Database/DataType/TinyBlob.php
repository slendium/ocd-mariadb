<?php

namespace Slendium\OcdMariaDb\Database\DataType;

use Override;

use Slendium\OcdMariaDb\Database\DataType;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class TinyBlob implements DataType, Binary {

	const NAME = 'tinyblob';

	const LIMIT = 255;

	private static self $instance;

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
		return $newType instanceof self
			|| $newType instanceof Blob
			|| $newType instanceof MediumBlob
			|| $newType instanceof LongBlob;
	}

}
