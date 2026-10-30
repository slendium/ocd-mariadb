<?php

namespace Slendium\OcdMariaDb\Collection;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Override;
use PDO;
use Traversable;

use Slendium\Ocd\Cursor\Countable as CursorCountable;
use Slendium\Ocd\Cursor\Filterable;
use Slendium\Ocd\Cursor\Scrollable;
use Slendium\Ocd\Query\Predicate;

use Slendium\OcdMariaDb\Document;
use Slendium\OcdMariaDb\Query\PredicateSqlizer;

/**
 * @internal
 * @implements IteratorAggregate<ArrayAccess<non-empty-string,mixed>&Countable&Traversable<non-empty-string,mixed>>
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class Cursor implements IteratorAggregate, CursorCountable, Filterable, Scrollable {

	#[Override]
	public ?Predicate $filter = null {
		get => $this->filter;
		set {
			$this->filter = $value;
			$this->queriedCount = null;
			$this->documents = null;
		}
	}

	/** @var int<0,max> */
	#[Override]
	public int $skip = 0 {
		get => $this->skip;
		set {
			$this->skip = $value;
			$this->documents = null;
		}
	}

	/** @var int<0,max> */
	#[Override]
	public int $limit = 0 {
		get => $this->limit;
		set {
			$this->limit = $value;
			$this->handleLimitChanged($value);
		}
	}

	/** @var ?list<ArrayAccess<non-empty-string,mixed>&Countable&Traversable<non-empty-string,mixed>> */
	private ?array $documents = null;

	/** @var ?int<0,max> */
	private ?int $queriedCount = null;

	public function __construct(

		private readonly PDO $pdo,

		/** @var non-empty-string */
		private readonly string $tableName,

	) { }

	#[Override]
	public function getIterator(): Traversable {
		yield from ($this->documents ??= $this->fetchDocuments());
	}

	#[Override]
	public function queryCount(): int {
		if ($this->queriedCount === null) {
			[ $query, $arguments ] = $this->createQuery(projection: 'COUNT(*)');

			$stmt = $this->pdo->prepare($query);
			$stmt->execute($arguments);
			$this->queriedCount = (int)$stmt->fetchColumn(0); // @phpstan-ignore assign.propertyType (is int<0,max>)
		}

		return $this->queriedCount; // @phpstan-ignore return.type (is int<0,max>)
	}

	/** @return list<ArrayAccess<non-empty-string,mixed>&Countable&Traversable<non-empty-string,mixed>> */
	private function fetchDocuments(): array {
		[ $query, $arguments ] = $this->createQuery();

		$stmt = $this->pdo->prepare($query);
		$stmt->execute($arguments);

		$documents = [ ];
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$documents[] = new Document($row); // @phpstan-ignore argument.type (row will be an assoc array)
		}
		return $documents;
	}

	/**
	 * Returns the current query as a tuple of the SQL-string and the argument map.
	 * @return array{ 0: string, 1: array<non-empty-string,mixed> }
	 */
	private function createQuery(string $projection = '*'): array {
		$query = "SELECT $projection FROM `{$this->tableName}`";
		$arguments = [ ];
		if ($this->filter !== null) {
			[ $whereClause, $arguments ] = (new PredicateSqlizer($this->tableName))->sqlize($this->filter);
			$query .= " WHERE $whereClause";
		}

		return [ $query, $arguments ];
	}

	/** @param int<0,max> $newLimit */
	private function handleLimitChanged(int $newLimit): void {
		if ($this->documents === null || $newLimit === \count($this->documents)) {
			return;
		}

		if ($newLimit > \count($this->documents)) {
			$this->documents = null;
			return;
		}

		$this->documents = \array_slice($this->documents, 0, $newLimit);
	}

}
