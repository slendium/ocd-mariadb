<?php

namespace Slendium\OcdMariaDb\Query;

use Override;

use Slendium\Ocd\Query\Update;
use Slendium\Ocd\Query\UpdateVisitor;

/**
 * @internal
 * @implements UpdateVisitor<string>
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class UpdateSqlizer extends Sqlizer implements UpdateVisitor {

	const ARGUMENT_PREFIX = 'u';

	public function __construct() {
		parent::__construct(self::ARGUMENT_PREFIX);
	}

	#[Override]
	public function visitAdd(Update\Add $stmt): string {
		if (\count($stmt->field->path) === 1) {
			return "`{$stmt->field->path[0]}` = `{$stmt->field->path[0]}` + :".$this->addArgument($stmt->amount);
		}
		throw new \Exception('Not implemented, waiting for slendium/ocd#93');
	}

	#[Override]
	public function visitAppend(Update\Append $stmt): string {
		throw new \Exception('Not implemented, waiting for slendium/ocd#93');
	}

	#[Override]
	public function visitMultiply(Update\Multiply $stmt): string {
		if (\count($stmt->field->path) === 1) {
			return "`{$stmt->field->path[0]}` = `{$stmt->field->path[0]}` * :".$this->addArgument($stmt->amount);
		}
		throw new \Exception('Not implemented, waiting for slendium/ocd#93');
	}

	#[Override]
	public function visitSet(Update\Set $stmt): string {
		if (\count($stmt->field->path) === 1) {
			return "`{$stmt->field->path[0]}` = :".$this->addArgument($stmt->value);
		}
		throw new \Exception('Not implemented, waiting for slendium/ocd#93');
	}

	#[Override]
	public function visitUnset(Update\Unset_ $stmt): string {
		if (\count($stmt->field->path) === 1) {
			return "`{$stmt->field->path[0]}` = DEFAULT";
		}
		throw new \Exception('Not implemented, waiting for slendium/ocd#93');
	}

	/**
	 * Returns a tuple containing the SQL string and the argument map.
	 * @param non-empty-list<Update> $stmts
	 * @return array{ 0: string, 1: array<non-empty-string,mixed> }
	 */
	public function sqlize(array $stmts): array {
		$this->clearArguments();

		$sql = $stmts
			|> (fn($x) => \array_map(fn($stmt) => $stmt->accept($this), $x))
			|> (fn($x) => \implode(', ', $x));
		return [ $sql, $this->getArguments() ];
	}

}
