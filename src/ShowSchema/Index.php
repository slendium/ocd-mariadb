<?php

namespace Slendium\OcdMariaDb\ShowSchema;

/**
 * A row from a `SHOW INDEX FROM ...` query.
 *
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class Index {

	const PRIMARY_KEY_NAME = 'PRIMARY';

	public bool $isPrimary {
		get => $this->Key_name === self::PRIMARY_KEY_NAME;
	}

	public function __construct(

		/** @var '1'|'0' */
		public readonly string $Non_unique,

		/** A custom name, 'PRIMARY' is reserved for the primary key */
		public readonly string $Key_name,

		/** A numeric string representing the place of the column name in the key, ascending */
		public readonly string $Seq_in_index,

		public readonly string $Column_name,

	) { }

}
