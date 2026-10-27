<?php

namespace Slendium\OcdMariaDb\Common;

/**
 * Parses `COLUMN_TYPE` strings from the MariaDB `information_schema.columns` table.
 *
 * @since 1.0
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class ColumnTypeParser {

	private const T_IDENTIFIER = 0;

	private const T_LEFT_PAREN = 1;

	private const T_RIGHT_PAREN = 2;

	private const T_COMMA = 3;

	private const T_INT = 4;

	private const T_STRING = 5;

	private const T_WHITESPACE = 6;

	private const IDENTIFIER_UNSIGNED = 'unsigned';

	private readonly string $input;

	/** @since 1.0 */
	public static function parse(string $input): ?ColumnType {
		$parser = new self($input);
		// 1. Parse the data type (such as `varchar` or `enum`)
		$token = $parser->scan();
		if ($token === null || $token[0] !== self::T_IDENTIFIER) {
			return null;
		}
		$dataType = $token[1]; /** @var non-empty-string $dataType */

		// 2. Return if there is no argument list directly following the data type
		$lParen = $parser->scan();
		if ($lParen === null) {
			return new ColumnType($dataType); // valid, identifier without arguments (eg. `float`)
		}

		// 3a. Check if the expression ends with whitespace + 'unsigned'
		if ($lParen[0] === self::T_WHITESPACE) {
			$unsigned = $parser->scan();
			return self::isUnsignedIdentifier($unsigned) && $parser->isAtEnd()
				? new ColumnType($dataType, [ ], true)
				: null;
		// 3b. Otherwise, only a T_LEFT_PAREN can continue the expression
		} else if ($lParen[0] !== self::T_LEFT_PAREN) {
			return null;
		}

		// 4. Parse the argument list
		$arguments = [ ];
		$argumentsClosed = false;
		while (($token = $parser->scanIgnoringWhitespace()) !== null) {
			if ($token[0] === self::T_STRING) {
				$arguments[] = $token[1];
			} else if ($token[0] === self::T_INT) {
				$arguments[] = (int)$token[1];
			} else if ($token[0] === self::T_RIGHT_PAREN) {
				break;
			} else {
				return null; // expecting argument at this position
			}

			$endToken = $parser->scanIgnoringWhitespace();
			if ($endToken !== null) { // @phpstan-ignore notIdentical.alwaysTrue (bug?)
				if ($endToken[0] === self::T_RIGHT_PAREN) {
					$argumentsClosed = true;
					break;
				} else if ($endToken[0] !== self::T_COMMA) {
					return null;
				}
			}
		}

		// 5. Expression can end here directly, or with ')' or with 'unsigned'
		$rParen = $parser->scanIgnoringWhitespace();
		if ($rParen === null
			|| $rParen[0] === self::T_RIGHT_PAREN && !$argumentsClosed && $parser->isAtEnd()
			|| ($isUnsigned = self::isUnsignedIdentifier($rParen)) && $parser->isAtEnd()
		) {
			return new ColumnType($dataType, $arguments, $isUnsigned ?? false);
		}
		return null;
	}

	private function __construct(

		string $input,

		private int $start = 0,

		private int $current = 0,

	) {
		$this->input = \trim($input);
	}

	/** @return ?array{ 0: self::T_*, 1: string } */
	private function scanIgnoringWhitespace(): ?array {
		while (true) {
			$token = $this->scan();
			if ($token === null || $token[0] !== self::T_WHITESPACE) {
				return $token;
			}
		}
	}

	/** @return ?array{ 0: self::T_*, 1: string } */
	private function scan(): ?array {
		$this->start = $this->current;

		$char = $this->consume();
		return match($char) {
			'(' => [ self::T_LEFT_PAREN, $char ],
			')' => [ self::T_RIGHT_PAREN, $char ],
			',' => [ self::T_COMMA, $char ],
			' ' => $this->scanWhitespace(),
			"'" => $this->scanString(),
			'0', '1', '2', '3', '4', '5', '6', '7', '8', '9' => $this->scanInt(),
			default => self::isAlpha($char)
				? $this->scanIdentifier()
				: null // unknown character
		};
	}

	/** @return array{ 0: self::T_WHITESPACE, 1: '' } */
	private function scanWhiteSpace(): array {
		while ($this->peek() === ' ' && !$this->isAtEnd()) {
			$this->advance();
		}

		return [ self::T_WHITESPACE, '' ];
	}

	/** @return array{ 0: self::T_STRING, 1: string } */
	private function scanString(): ?array {
		$str = '';
		while (true) {
			if ($this->isAtEnd()) {
				return null; // unterminated string
			}
			if ($this->peek() === "'") {
				if ($this->peekNext() === "'") {
					$str .= "'";
					$this->advance(2);
				} else {
					break;
				}
			} else if ($this->peek() === '\\') {
				$char = self::convertEscapeCharToRealChar($this->peekNext());
				if ($char !== null) {
					$str .= $char;
					$this->advance(2);
				} else {
					$this->advance(); // a single backslash seems to be ignored
				}
			} else {
				$str .= $this->consume();
			}
		}

		$this->advance(); // closing quote
		return [ self::T_STRING, $str ];
	}

	/** @return ?array{ 0: self::T_INT, 1: numeric-string } */
	private function scanInt(): ?array {
		while (self::isDigit($this->peek())) {
			$this->advance();
		}

		$number = \substr($this->input, $this->start, $this->current - $this->start);
		return \is_numeric($number)
			? [ self::T_INT, $number ]
			: null;
	}

	/** @return array{ 0: self::T_IDENTIFIER, 1: string } */
	private function scanIdentifier(): array {
		while (self::isAlpha($this->peek())) {
			$this->advance();
		}

		return [ self::T_IDENTIFIER, \substr($this->input, $this->start, $this->current - $this->start) ];
	}

	private function consume(): string {
		$out = $this->input[$this->current] ?? '';
		$this->current += 1;
		return $out;
	}

	/** @param int<0,max> $amount */
	private function advance(int $amount = 1): void {
		$this->current += $amount;
	}

	private function peek(): string {
		return $this->isAtEnd()
			? ''
			: $this->input[$this->current];
	}

	private function peekNext(): string {
		return $this->current + 1 < \strlen($this->input)
			? $this->input[$this->current + 1]
			: '';
	}

	private function isAtEnd(): bool {
		return $this->current >= \strlen($this->input);
	}

	/** @param ?array{ 0: self::T_*, 1: string } $token */
	private static function isUnsignedIdentifier(?array $token): bool {
		return $token !== null
			&& $token[0] === self::T_IDENTIFIER
			&& $token[1] === self::IDENTIFIER_UNSIGNED;
	}

	private static function convertEscapeCharToRealChar(string $escape): ?string {
		// the behavior of COLUMN_TYPE character escaping behaves different from that of string literals

		// \' will be output as `''`, ie. `enum('\'')` results in `enum('''')`
		// \b (backspace) is output directly, ie. `enum('\b')` results in `enum(')` visually
		// \t is output directly, ie. `enum('\t')` results in `enum(	)` visually
		// \Z (Control-Z) is output directly (my terminal could not render it)
		// \\ is actually a single backslash, but this can only be seen by SELECTing the table
		//		ie. `enum('\\')` results in `enum('\\')` when using DESCRIBE
		//		but it results in `\` when using SELECT

		return match($escape) {
			'0' => "\0",
			'n' => "\n",
			'r' => "\r",
			'\\' => '\\',
			default => null
		};
	}

	private static function isAlpha(string $char): bool {
		return $char !== '' && \ord($char) >= 97 && \ord($char) <= 122;
	}

	private static function isDigit(string $char): bool {
		return $char !== '' && \ord($char) >= 48 && \ord($char) <= 57;
	}

}
