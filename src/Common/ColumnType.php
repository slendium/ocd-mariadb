<?php

namespace Slendium\OcdMariaDb\Common;

/**
 * Information about a MariaDB column type.
 *
 * @since 1.0
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class ColumnType {

	/** @since 1.0 */
	public function __construct(

		/** @var non-empty-string */
		public string $name,

		/** @var list<string|int> */
		public array $arguments = [ ],

		public bool $isUnsigned = false,

	) { }

	/** @param int<0,max> $index */
	public function getIntArg(int $index): ?int {
		return isset($this->arguments[$index]) && \is_int($this->arguments[$index])
			? $this->arguments[$index]
			: null;
	}

}
