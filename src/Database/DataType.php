<?php

namespace Slendium\OcdMariaDb\Database;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
interface DataType {

	/** @return non-empty-string */
	public function toSql(): string;

	/**
	 * Checks if any value from the current type safely fits in a column of the given type, without
	 * causing data loss or requiring conversion.
	 * @return bool `true` if the new type fits all possible values of the current type, `false` if
	 *  not or if the new type is unknown
	 */
	public function fitsIn(self $newType): bool;

}
