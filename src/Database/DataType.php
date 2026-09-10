<?php

namespace Slendium\OcdMariaDb\Database;

use LogicException;

use Slendium\Ocd\Schema\StorageClass;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
enum DataType : string {

	case TinyInt = 'tinyint';

	case Int = 'int';

	case Double = 'double';

	case VarChar = 'varchar';

	case Text = 'text';

	case Uuid = 'uuid';

	public static function fromStorageClass(StorageClass $storageClass): self {
		return match($storageClass) {
			StorageClass::String => self::VarChar,
			StorageClass::Float => self::Double,
			StorageClass::Int => self::Int,
			StorageClass::Bool => self::TinyInt
		};
	}

	public function isInteger(): bool {
		return match($this) {
			self::TinyInt,
			self::Int
				=> true,
			default => false
		};
	}

	public function getIntSize(): int {
		return match($this) {
			self::TinyInt => 1,
			self::Int => 4,
			default => throw new LogicException('Expected DataType to be an integer variant')
		};
	}

	public function toSql(?int $characterCount = null): string {
		return match($this) {
			self::VarChar => "{$this->value}(".($characterCount ?? 255).')',
			default => $this->value
		};
	}

}
