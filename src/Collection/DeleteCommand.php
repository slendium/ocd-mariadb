<?php

namespace Slendium\OcdMariaDb\Collection;

use Override;
use PDO;

use Slendium\Ocd\Query\Predicate;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class DeleteCommand extends BaseWriteCommand {

	/** @param non-empty-string $table */
	public function __construct(

		PDO $pdo,

		string $table,

		private readonly Predicate $filter,

	) {
		parent::__construct($pdo, $table);
	}

	#[Override]
	public function execute(): void {
		$update = Query::delete($this->table, $this->filter);

		$this->pdo->prepare($update->sql)
			->execute($update->arguments);
	}

}
