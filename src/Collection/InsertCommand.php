<?php

namespace Slendium\OcdMariaDb\Collection;

use ArrayAccess;
use Countable;
use Override;
use PDO;
use Traversable;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class InsertCommand extends BaseWriteCommand {

	/** @param non-empty-string $table */
	public function __construct(

		PDO $pdo,

		string $table,

		/** @var non-empty-list<ArrayAccess<non-empty-string,mixed>&Countable&Traversable<non-empty-string,mixed>> */
		private readonly array $documents,

	) {
		parent::__construct($pdo, $table);
	}

	#[Override]
	public function execute(): void {
		foreach ($this->documents as $document) {
			$insert = Query::insert($this->table, $document);

			$this->pdo->prepare($insert->sql)
				->execute($insert->arguments);
		}
	}

}
