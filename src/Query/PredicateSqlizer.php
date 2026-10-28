<?php

namespace Slendium\OcdMariaDb\Query;

use Override;

use Slendium\Ocd\Query\Predicate;
use Slendium\Ocd\Query\PredicateVisitor;

/**
 * @internal
 * @implements PredicateVisitor<string>
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class PredicateSqlizer extends Sqlizer implements PredicateVisitor {

	public function __construct(

		/** @var non-empty-string */
		private string $tableName,

	) { }

	#[Override]
	public function visitAnd(Predicate\And_ $expr): string {
		$out = '';
		foreach ($expr->inputs as $i => $input) {
			if ($i !== 0) {
				$out .= ' AND ';
			}
			$out .= $input->accept($this);
		}
		return $out;
	}

	#[Override]
	public function visitOr(Predicate\Or_ $expr): string {
		$out = '';
		foreach ($expr->inputs as $i => $input) {
			if ($i !== 0) {
				$out .= ' OR ';
			}
			$out .= $input->accept($this);
		}
		return $out;
	}

	#[Override]
	public function visitExists(Predicate\Exists $expr): string {
		if (\count($expr->field->path) === 1) {
			$subQuery = $this->selectColumnSql($expr->field->path[0]);
			return "EXISTS($subQuery)";
		}
		return self::pathExists($expr->field->path);
	}

	#[Override]
	public function visitNotExists(Predicate\NotExists $expr): string {
		if (\count($expr->field->path) === 1) {
			$subQuery = $this->selectColumnSql($expr->field->path[0]);
			return "NOT EXISTS($subQuery)";
		}
		return '!'.self::pathExists($expr->field->path);
	}

	#[Override]
	public function visitEqual(Predicate\Equal $expr): string {
		if ($expr->lhs === null) {
			return $this->sqlizeLiteral($expr->rhs, self::inJsonContext($expr->rhs)).' IS NULL';
		} else if ($expr->rhs === null) {
			return $this->sqlizeLiteral($expr->lhs, self::inJsonContext($expr->lhs)).' IS NULL';
		}
		return $this->binaryOperator($expr, '=');
	}

	#[Override]
	public function visitNotEqual(Predicate\NotEqual $expr): string {
		if ($expr->lhs === null) {
			return $this->sqlizeLiteral($expr->rhs, self::inJsonContext($expr->rhs)).' IS NOT NULL';
		} else if ($expr->rhs === null) {
			return $this->sqlizeLiteral($expr->lhs, self::inJsonContext($expr->lhs)).' IS NOT NULL';
		}
		return $this->binaryOperator($expr, '!=');
	}

	#[Override]
	public function visitGreaterThan(Predicate\GreaterThan $expr): string {
		return $this->binaryOperator($expr, '>');
	}

	#[Override]
	public function visitGreaterThanOrEqual(Predicate\GreaterThanOrEqual $expr): string {
		return $this->binaryOperator($expr, '>=');
	}

	#[Override]
	public function visitLessThan(Predicate\LessThan $expr): string {
		return $this->binaryOperator($expr, '<');
	}

	#[Override]
	public function visitLessThanOrEqual(Predicate\LessThanOrEqual $expr): string {
		return $this->binaryOperator($expr, '<=');
	}

	#[Override]
	public function visitMatchAll(Predicate\MatchAll $expr): string {
		$fieldSelector = self::pathValue($expr->field->path);
		$inJson = self::inJsonContext($expr->field);

		return \count($expr->values) > 1
			? "JSON_EQUALS($fieldSelector, JSON_ARRAY(".$this->sqlizeList($expr->values, $inJson)."))"
			: $expr->values[0] === null
			? "$fieldSelector IS NULL"
			: "$fieldSelector = ".$this->sqlizeLiteral($expr->values[0], $inJson);
	}

	#[Override]
	public function visitMatchSome(Predicate\MatchSome $expr): string {
		$inJson = self::inJsonContext($expr->field);
		return self::pathValue($expr->field->path).' IN '.$this->sqlizeList($expr->values, $inJson);
	}

	#[Override]
	public function visitMatchNone(Predicate\MatchNone $expr): string {
		$inJson = self::inJsonContext($expr->field);
		return self::pathValue($expr->field->path).' NOT IN '.$this->sqlizeList($expr->values, $inJson);
	}

	#[Override]
	public function visitMatchRegex(Predicate\MatchRegex $expr): string {
		return self::pathValue($expr->field->path).' REGEXP :'.$this->addArgument($expr->regex);
	}

	/**
	 * Returns a tuple containing the SQL string and the argument map.
	 * @return array{ 0: string, 1: array<non-empty-string,mixed> }
	 */
	public function sqlize(Predicate $expr): array {
		$this->clearArguments();

		$sql = $expr->accept($this);
		return [ $sql, $this->getArguments() ];
	}

	private function selectColumnSql(string $columnName): string {
		return 'SELECT 1 FROM `INFORMATION_SCHEMA.COLUMNS`'
			.' WHERE TABLE_SCHEMA = SCHEMA()'
			.' AND TABLE_NAME = :'.$this->addArgument($this->tableName)
			.' AND COLUMN_NAME = :'.$this->addArgument($columnName);
	}

	private function binaryOperator(Predicate $expr, string $operator): string {
		// TODO in ocd v0.3 a separate BinaryOperator interface for predicates will be introduced,
		// which would make the code below work better with static analysis

		$inJson = self::inJsonContext($expr->lhs, $expr->rhs); // @phpstan-ignore property.notFound, property.notFound
		return $this->sqlizeLiteral($expr->lhs, $inJson) // @phpstan-ignore argument.type, property.notFound
			." $operator "
			.$this->sqlizeLiteral($expr->rhs, $inJson); // @phpstan-ignore argument.type, property.notFound
	}

}
