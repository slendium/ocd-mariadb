<?php

namespace Slendium\OcdMariaDb;

use Override;
use PDO;
use Traversable;

use Slendium\Ocd\Collection as ICollection;
use Slendium\Ocd\Collection\WriteCommand;
use Slendium\Ocd\Cursor\Filterable;
use Slendium\Ocd\Cursor\Scrollable;
use Slendium\Ocd\Predicate;
use Slendium\Ocd\Update;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class Collection implements ICollection {

	public function __construct(

		private PDO $pdo,

		/** @var non-empty-string */
		private string $name,

	) { }

	#[Override]
	public function openCursor(): Filterable&Scrollable&Traversable {
		throw new \Exception('Not implemented');
	}

	#[Override]
	public function startInsert(array $documents): WriteCommand {
		return new Collection\InsertCommand($this->pdo, $this->name, $documents);
	}

	#[Override]
	public function startUpdate(Predicate $filter, array $updates): WriteCommand {
		throw new \Exception('Not implemented');
	}

	#[Override]
	public function startDelete(Predicate $filter): WriteCommand {
		throw new \Exception('Not implemented');
	}

}
