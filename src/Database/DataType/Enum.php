<?php

namespace Slendium\OcdMariaDb\Database\DataType;

use BackedEnum;
use LogicException;
use Override;

use Slendium\OcdMariaDb\Common\LiteralSqlizer;
use Slendium\OcdMariaDb\Database\DataType;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class Enum implements DataType {

	const NAME = 'enum';

	/** @param class-string<BackedEnum> $class */
	public static function fromBackedEnum(string $class): self {
		$cases = [ ];
		foreach ([ $class, 'cases' ]() as $case) {
			$cases[] = (string)$case->value;
		}
		return new self($cases);
	}

	public function __construct(

		/** @var list<string> */
		public array $cases,

	) { }

	#[Override]
	public function toSql(): string {
		$formattedCases = \array_map(LiteralSqlizer::formatString(...), $this->cases)
			|> (fn($x) => \implode(', ', $x));

		return self::NAME."($formattedCases)";
	}

	#[Override]
	public function fitsIn(DataType $newType): bool {
		return $newType instanceof self && empty(\array_diff($this->cases, $newType->cases));
	}

}
