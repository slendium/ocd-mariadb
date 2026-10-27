<?php

namespace Slendium\OcdMariaDb\Database\DataType;

/**
 * Marker for freeform character-based types.
 *
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
interface Textual {

	/** @var int<0,max> */
	public int $characterLimit { get; }

}
