<?php

namespace Slendium\OcdMariaDb\Common\Diff;

/**
 * @internal
 * @template TItem of Identifiable
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class ListAddition {

	public function __construct(

		/** @var TItem */
		public object $item,

		/** The identity of the item after which it was added in the list */
		public ?string $after,

	) { }

}
