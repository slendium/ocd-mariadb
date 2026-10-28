<?php

namespace Slendium\OcdMariaDb;

use ArrayAccess;
use ArrayObject;
use Countable;
use IteratorAggregate;
use LogicException;
use Override;
use Traversable;

/**
 * @internal
 * @implements ArrayAccess<non-empty-string,mixed>
 * @implements IteratorAggregate<non-empty-string,mixed>
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class Document implements ArrayAccess, Countable, IteratorAggregate {

	public function __construct(

		/** @var array<non-empty-string,mixed> */
		private array $row,

	) { }

	#[Override]
	public function offsetExists(mixed $offset): bool {
		return isset($this->row[$offset]);
	}

	#[Override]
	public function offsetGet(mixed $offset): mixed {
		return $this->row[$offset] ?? null;
	}

	#[Override]
	public function offsetSet(mixed $offset, mixed $value): void {
		throw new LogicException('Unexpected attempt to mutate document');
	}

	#[Override]
	public function offsetUnset(mixed $offset): void {
		throw new LogicException('Unexpected attempt to mutate document');
	}

	#[Override]
	public function count(): int {
		return \count($this->row);
	}

	#[Override]
	public function getIterator(): Traversable {
		return new ArrayObject($this->row)->getIterator(); // @phpstan-ignore return.type (ArrayObject seems to destroy the non-empty part of the key)
	}

}
