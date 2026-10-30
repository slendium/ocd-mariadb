<?php

namespace Slendium\OcdMariaDb\Query;

use BackedEnum;
use DateTimeInterface;

use Slendium\Ocd\Common\FieldPath;

use Slendium\OcdMariaDb\Common\LiteralSqlizer;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
abstract class Sqlizer {

	/** @var array<non-empty-string,mixed> */
	private array $arguments = [ ];

	/** @param non-empty-list<non-empty-string> $fieldPath */
	protected static function pathExists(array $fieldPath): string {
		$jsonPath = self::sqlizePath(\array_slice($fieldPath, 1));
		return "JSON_EXISTS(`{$fieldPath[0]}`, '$jsonPath')";
	}

	/** @param non-empty-list<non-empty-string> $fieldPath */
	protected static function pathValue(array $fieldPath): string {
		return \count($fieldPath) === 1
			? "`{$fieldPath[0]}`"
			: "JSON_VALUE(`{$fieldPath[0]}`, '".self::sqlizePath(\array_slice($fieldPath, 1))."')";
	}

	protected static function inJsonContext(mixed ...$maybeFields): bool {
		foreach ($maybeFields as $maybeField) {
			if ($maybeField instanceof FieldPath && \count($maybeField->path) > 1) {
				return true;
			}
		}
		return false;
	}

	/** @param non-empty-list<FieldPath|DateTimeInterface|BackedEnum|string|float|int|bool|null> $values */
	protected final function sqlizeList(array $values, bool $inJson): string {
		$out = '';
		foreach ($values as $i => $value) {
			if ($i !== 0) {
				$out .= ', ';
			}
			$out .= $this->sqlizeLiteral($value, $inJson);
		}
		return $out;
	}

	protected final function sqlizeLiteral(FieldPath|DateTimeInterface|BackedEnum|string|float|int|bool|null $literal, bool $inJson): string {
		if (\is_string($literal) || \is_float($literal) || \is_int($literal)) {
			return ':'.$this->addArgument($literal);
		} else if (\is_bool($literal)) {
			return $literal
				? LiteralSqlizer::TRUE
				: LiteralSqlizer::FALSE;
		} else if ($literal instanceof FieldPath) {
			return self::pathValue($literal->path);
		} else if ($literal instanceof BackedEnum) {
			return ':'.$this->addArgument((string)$literal->value);
		} else if ($literal instanceof DateTimeInterface) {
			return $inJson
				? ':'.$this->addArgument($literal->getTimestamp())
				: ':'.$this->addArgument($literal->format(LiteralSqlizer::DATETIME_FORMAT));
		}
		return LiteralSqlizer::NULL;
	}

	public function __construct(

		/** @var non-empty-string */
		private string $argumentPrefix = 'a',

	) { }

	/**
	 * @param mixed $value The argument value
	 * @return non-empty-string The argument name
	 */
	protected final function addArgument(mixed $value): string {
		$name = $this->argumentPrefix.\count($this->arguments);
		$this->arguments[$name] = $value;
		return $name;
	}

	/** @return array<non-empty-string,mixed> */
	protected final function getArguments(): array {
		return $this->arguments;
	}

	protected final function clearArguments(): void {
		$this->arguments = [ ];
	}

	/** @param list<non-empty-string> $path */
	private static function sqlizePath(array $path): string {
		$out = '$';
		foreach ($path as $part) {
			$out .= ".$part";
		}
		return $out;
	}

}
