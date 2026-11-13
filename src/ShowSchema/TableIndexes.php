<?php

namespace Slendium\OcdMariaDb\ShowSchema;

use PDO;

use Slendium\OcdMariaDb\Common\TableKey;
use Slendium\OcdMariaDb\Common\PartialObject;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class TableIndexes {

	public ?TableKey $primaryKey {
		get => $this->get_primaryKey();
	}

	public static function fetch(PDO $pdo, string $table): self {
		$rows = $pdo->query("SHOW INDEX FROM `$table`")
			->fetchAll(PDO::FETCH_ASSOC); // @phpstan-ignore method.nonObject (result wont be false)

		/** @var list<array<mixed>> $rows */
		$indexes = \array_map(fn($row) => PartialObject::createFromRow(Index::class, $row), $rows);

		return new self($indexes);
	}

	public function __construct(

		/** @var list<Index> */
		public readonly array $indexes,

	) { }

	private function get_primaryKey(): ?TableKey {
		$primaries = \array_filter($this->indexes, fn($index) => $index->isPrimary);

		return \count($primaries) > 0
			? TableKeyFactory::createFromIndexes(\array_values($primaries))
			: null;
	}

}
