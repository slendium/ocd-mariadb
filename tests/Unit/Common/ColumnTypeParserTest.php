<?php

namespace Slendium\OcdMariaDbTests\Common;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use Slendium\OcdMariaDb\Common\ColumnType;
use Slendium\OcdMariaDb\Common\ColumnTypeParser;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class ColumnTypeParserTest extends TestCase {

	public static function parseCases(): iterable { // @phpstan-ignore missingType.iterableValue
		yield [ '', null ];
		yield [ '(1)', null ];
		yield [ 'float', new ColumnType('float') ];
		yield [ ' float', new ColumnType('float') ];
		yield [ 'float unsigned', new ColumnType('float', isUnsigned: true) ];
		yield [ 'float(30,10) unsigned', new ColumnType('float', [ 30, 10 ], true) ];
		yield [ 'float()', new ColumnType('float') ];
		yield [ 'float() ', new ColumnType('float') ];
		yield [ 'float(', new ColumnType('float') ];
		yield [ 'float(1)', new ColumnType('float', [ 1 ]) ];
		yield [ 'float(1) ', new ColumnType('float', [ 1 ]) ];
		yield [ 'float(1,) ', new ColumnType('float', [ 1 ]) ];
		yield [ 'float(1,', new ColumnType('float', [ 1 ]) ];
		yield [ 'float(1,,)', null ];
		yield [ 'float( 1 )', new ColumnType('float', [ 1 ]) ];
		yield [ 'float( 1, )', new ColumnType('float', [ 1 ]) ];
		yield [ 'float(1,)', new ColumnType('float', [ 1 ]) ];
		yield [ 'float(1))', null ];
		yield [ 'float(1 1)', null ];
		yield [ 'float(1 1', null ];
		yield [ 'float1', null ];
		yield [ "float'string?'", null ];
		yield [ "float('a', 'b')'garbage'", null ];
		yield [ "float('a', 'b')67", null ];
		yield [ 'float((', null ];
		yield [ 'float(a)', null ];
		yield [ 'double(255,30)', new ColumnType('double', [ 255, 30 ]) ];
		yield [ "enum('a', 'b', 'c')", new ColumnType('enum', [ 'a', 'b', 'c' ]) ];
		yield [ "enum('''a''', '''b', 'c''')", new ColumnType('enum', [ "'a'", "'b", "c'" ]) ];
		yield [ "enum('\\\\a', '\\n', '\\r', '\\\\', '\\0')", new ColumnType('enum', [ '\\a', "\n", "\r", '\\', "\0" ]) ];
	}

	#[DataProvider('parseCases')]
	public function test_parse_shouldReturnExpectedResult(string $input, ?ColumnType $expectedResult): void {
		// Act
		$result = ColumnTypeParser::parse($input);

		// Assert
		if ($expectedResult === null) {
			$this->assertNull($result);
		} else {
			$this->assertNotNull($result);
			$this->assertSame($expectedResult->name, $result->name);
			$this->assertSame($expectedResult->arguments, $result->arguments);
			$this->assertSame($expectedResult->isUnsigned, $result->isUnsigned);
		}
	}

}
