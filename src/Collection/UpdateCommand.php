<?php

namespace Slendium\OcdMariaDb\Collection;

use Override;
use PDO;

use Slendium\Ocd\Query\Predicate;
use Slendium\Ocd\Query\Update;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class UpdateCommand extends BaseWriteCommand {

	/** @param non-empty-string $table */
	public function __construct(

		PDO $pdo,

		string $table,

		private readonly Predicate $predicate,

		/** @var non-empty-list<Update> */
		private readonly array $updates,

	) {
		parent::__construct($pdo, $table);
	}

	#[Override]
	public function execute(): void {
		$update = Query::update($this->table, $this->updates, $this->predicate);

		$this->pdo->prepare($update->sql)
			->execute($update->arguments);
	}

}
