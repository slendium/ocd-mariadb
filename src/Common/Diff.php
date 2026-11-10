<?php

namespace Slendium\OcdMariaDb\Common;

/**
 * The diff(erence) between two values.
 *
 * @internal
 * @template T
 * @author C. Fahner
 * @copyright Slendium 2026
 */
interface Diff {

	/** @var T */
	public mixed $before { get; }

	/** @var T */
	public mixed $after { get; }

}
