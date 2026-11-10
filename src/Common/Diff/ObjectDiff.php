<?php

namespace Slendium\OcdMariaDb\Common\Diff;

use Slendium\OcdMariaDb\Common\Diff;

/**
 * @internal
 * @template TItem of object
 * @extends Diff<TItem>
 * @author C. Fahner
 * @copyright Slendium 2026
 */
interface ObjectDiff extends Diff {

	/**
	 * @param TItem $before
	 * @param TItem $after
	 */
	public static function diff(object $before, object $after): ?static;

}
