<?php

namespace Slendium\OcdMariaDb\Database;

/**
 * @internal
 * @template T
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class Change {

	public function __construct(

		/** @var T */
		public mixed $before,

		/** @var T */
		public mixed $after,

	) { }

}
