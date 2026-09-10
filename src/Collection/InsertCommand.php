<?php

namespace Slendium\OcdMariaDb\Collection;

use ArrayAccess;
use Countable;
use Override;
use PDO;
use Traversable;

use Slendium\Ocd\Collection\WriteCommand;
use Slendium\Ocd\Collection\WriteOptions;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class InsertCommand implements WriteCommand {

	#[Override]
	public WriteOptions $writeOptions;

	public function __construct(

		private PDO $pdo,

		/** @var non-empty-string */
		private string $table,

		/** @var non-empty-list<ArrayAccess<non-empty-string,mixed>&Countable&Traversable<non-empty-string,mixed>> */
		private array $documents,

	) {
		$this->writeOptions = new WriteOptions;
	}

	#[Override]
	public function execute(): void {
		foreach ($this->documents as $document) {
			$insert = InsertQuery::create($this->table, $document);
			$this->pdo->prepare($insert->query)
				->execute($insert->parameters);
		}
	}

}
