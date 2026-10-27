<?php

namespace Slendium\OcdMariaDb\Common;

use BackedEnum;
use DateTimeInterface;
use LogicException;

/**
 * Utilities for converting literal values to SQL.
 *
 * > **DANGER:**
 * > Do not use these methods in data queries to defend against SQL injection.
 * > These methods are used in situations where the input is controlled by the developer and strings
 * > still need to be escaped, but where prepared statements are not allowed (such as `ALTER TABLE` statements).
 * > Use [prepared statements](https://www.php.net/manual/en/pdo.prepare.php) whenever you need to
 * > use an untrusted string in a query.
 *
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class LiteralSqlizer {

	const TRUE = '1';

	const FALSE = '0';

	const NULL = 'NULL';

	private const DATETIME_FORMAT = 'Y-m-d H:i:s';

	/** @return non-empty-string */
	public static function formatValue(mixed $value): string {
		return match(true) {
			\is_array($value) => self::formatString(\json_encode($value)), // @phpstan-ignore argument.type (json_encode wont be false)
			\is_string($value) => self::formatString($value),
			(\is_int($value) || \is_float($value)) && \is_finite($value) => (string)$value,
			\is_bool($value) => $value
				? self::TRUE
				: self::FALSE,
			$value instanceof DateTimeInterface => self::formatDateTime($value),
			$value instanceof BackedEnum => \is_string($value->value)
				? self::formatString($value->value)
				: (string)$value->value,
			$value === null => self::NULL,
			default => throw new LogicException('Expected a supported literal value type, not '.\get_debug_type($value))
		};
	}

	/** @return non-empty-string */
	public static function formatDateTime(DateTimeInterface $value): string {
		return $value->format(self::DATETIME_FORMAT);
	}

	/** @return non-empty-string */
	public static function formatString(string $value): string {
		$out = '';
		foreach (\mb_str_split($value) as $char) {
			$out .= match($char) {
				"\0" => '\\0',
				"\b" => '\\b',
				"\r" => '\\r',
				"\t" => '\\t',
				"\n" => '\\n',
				chr(26) => '\\Z',
				'\\' => '\\\\',
				"'" => "\\'",
				default => $char
			};
		}
		return "'$out'";
	}

}
