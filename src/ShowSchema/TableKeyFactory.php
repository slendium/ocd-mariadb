<?php

namespace Slendium\OcdMariaDb\ShowSchema;

use Slendium\OcdMariaDb\Common\TableKey;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class TableKeyFactory {

	/** @param non-empty-list<Index> $indexes */
	public static function createFromIndexes(array $indexes): TableKey {
		\usort($indexes, fn($a, $b) => $a->Seq_in_index <=> $b->Seq_in_index);
		return new TableKey(\array_map(fn($idx) => $idx->Column_name, $indexes));
	}

	private function __construct() { }

}
