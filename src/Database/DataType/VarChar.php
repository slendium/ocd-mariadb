<?php

namespace Slendium\OcdMariaDb\Database\DataType;

use LogicException;
use Override;

use Slendium\OcdMariaDb\Database\DataType;

/**
 * The preferred type for string data.
 *
 * Varchar is preferred because it can be indexed and it supports default values or `null`, which
 * text columns don't support.
 *
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class VarChar implements DataType, Textual {

	const NAME = 'varchar';

	const LIMIT = 65_532;

	public function __construct(

		/** @var int<0,self::LIMIT> */
		#[Override]
		public int $characterLimit,

	) { }

	#[Override]
	public function toSql(): string {
		return self::NAME."({$this->characterLimit})";
	}

	#[Override]
	public function fitsIn(DataType $newType): bool {
		return $newType instanceof Textual && $newType->characterLimit >= $this->characterLimit;
	}

}
