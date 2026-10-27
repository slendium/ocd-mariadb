<?php

namespace Slendium\OcdMariaDbTests\Common;

use PHPUnit\Framework\TestCase;

use Slendium\OcdMariaDb\Common\ColumnType;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class ColumnTypeTest extends TestCase {

	public function test___construct_shouldSetExpectedDefaults(): void {
		$expectedArgumentsDefault = [ ];
		$expectedIsUnsignedDefault = false;

		$sut = new ColumnType('foo');

		$this->assertSame($expectedArgumentsDefault, $sut->arguments);
		$this->assertSame($expectedIsUnsignedDefault, $sut->isUnsigned);
	}

	public function test_getIntArg_shouldReturnIntWhenValueIsInt(): void {
		$expectedResult = 10;
		$sut = new ColumnType('foo', [ $expectedResult ]);

		$result = $sut->getIntArg(0);

		$this->assertSame($expectedResult, $result);
	}

	public function test_getIntArg_shouldReturnNullWhenValueDoesNotExist(): void {
		$sut = new ColumnType('foo', [ 1, 2, 3 ]);

		$result = $sut->getIntArg(3);

		$this->assertNull($result);
	}

	public function test_getIntArg_shouldReturnNullWhenValueIsString(): void {
		$sut = new ColumnType('foo', [ 'test', '1' ]);

		$result0 = $sut->getIntArg(0);
		$result1 = $sut->getIntArg(1);

		$this->assertNull($result0);
		$this->assertNull($result1);
	}

}
