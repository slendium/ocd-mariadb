<?php

namespace Slendium\OcdMariaDb\Common\Diff;

use Override;

use Slendium\OcdMariaDb\Common\Diff;

/**
 * @internal
 * @template T
 * @implements Diff<T>
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class ChangedValue implements Diff {

	public function __construct(

		/** @var T */
		#[Override]
		public mixed $before,

		/** @var T */
		#[Override]
		public mixed $after,

	) { }

}
