<?php

namespace Slendium\OcdMariaDb\Database;

use BackedEnum;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use RuntimeException;

use Slendium\Ocd\Common\Blob as BlobValue;
use Slendium\Ocd\Entity;
use Slendium\Ocd\Schema\Type;
use Slendium\Ocd\Schema\TypeInfo;

use Slendium\OcdMariaDb\Common\ColumnTypeParser;
use Slendium\OcdMariaDb\Database\DataType;
use Slendium\OcdMariaDb\Database\DataType\BigInt;
use Slendium\OcdMariaDb\Database\DataType\Double_;
use Slendium\OcdMariaDb\Database\DataType\Float_;
use Slendium\OcdMariaDb\Database\DataType\Int_;
use Slendium\OcdMariaDb\Database\DataType\MediumInt;
use Slendium\OcdMariaDb\Database\DataType\SmallInt;
use Slendium\OcdMariaDb\Database\DataType\TinyInt;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class DataTypeFactory {

	/**
	 * Creates a DataType instance from an `information_schema.columns` row.
	 *
	 * * DATA_TYPE contains the bare name, such as `varchar` for `varchar(200)`
	 * * COLUMN_TYPE contains the parameterized name, such as `float(10,5)`
	 *
	 * @param array{
	 *  DATA_TYPE: non-empty-string,
	 *  CHARACTER_MAXIMUM_LENGTH: ?int<0,65532>,
	 *  DATETIME_PRECISION: ?int<0,6>,
	 *  COLUMN_TYPE: non-empty-string
	 * } $row
	 */
	public static function createFromInformationSchemaRow(array $row): DataType {
		return match($row['DATA_TYPE']) {
			DataType\VarChar::NAME => new DataType\VarChar($row['CHARACTER_MAXIMUM_LENGTH'] ?? 255),
			DataType\Uuid::NAME => DataType\Uuid::instance(),
			DataType\TinyBlob::NAME => DataType\TinyBlob::instance(),
			DataType\Blob::NAME => DataType\Blob::instance(),
			DataType\MediumBlob::NAME => DataType\MediumBlob::instance(),
			DataType\LongBlob::NAME => DataType\LongBlob::instance(),
			DataType\TinyText::NAME => DataType\TinyText::instance(),
			DataType\Text::NAME => DataType\Text::instance(),
			DataType\MediumText::NAME => DataType\MediumText::instance(),
			DataType\LongText::NAME => DataType\LongText::instance(),
			DataType\DateTime::NAME => new DataType\DateTime($row['DATETIME_PRECISION'] ?? 0),
			default => self::createFromColumnType($row['COLUMN_TYPE'])
		};
	}

	/** @param non-empty-string $columnType */
	private static function createFromColumnType(string $columnType): DataType {
		$type = ColumnTypeParser::parse($columnType);
		if ($type === null) {
			throw new RuntimeException("Unexpected COLUMN_TYPE parse error for value `$columnType`");
		}

		return match($type->name) {
			TinyInt::NAME => new TinyInt($type->getIntArg(0) ?? TinyInt::DEFAULT_DIGITS, $type->isUnsigned), // @phpstan-ignore argument.type
			SmallInt::NAME => new SmallInt($type->getIntArg(0) ?? SmallInt::DEFAULT_DIGITS, $type->isUnsigned), // @phpstan-ignore argument.type
			MediumInt::NAME => new MediumInt($type->getIntArg(0) ?? MediumInt::DEFAULT_DIGITS, $type->isUnsigned), // @phpstan-ignore argument.type
			Int_::NAME => new Int_($type->getIntArg(0) ?? Int_::DEFAULT_DIGITS, $type->isUnsigned), // @phpstan-ignore argument.type
			BigInt::NAME => new BigInt($type->getIntArg(0) ?? BigInt::DEFAULT_DIGITS, $type->isUnsigned), // @phpstan-ignore argument.type
			Float_::NAME => new Float_($type->getIntArg(0), $type->getIntArg(1), $type->isUnsigned), // @phpstan-ignore argument.type, argument.type
			Double_::NAME => new Double_($type->getIntArg(0), $type->getIntArg(1), $type->isUnsigned), // @phpstan-ignore argument.type, argument.type
			DataType\Enum::NAME => new DataType\Enum(\array_map(\strval(...), $type->arguments)),
			default => new DataType\Unknown($columnType)
		};
	}

	public static function createFromSchemaType(Type $type): DataType {
		$serializeType = TypeInfo::getSerializeType($type::class);

		return match($serializeType) {
			'array' => DataType\LongText::instance(),
			'string' => $type instanceof Type\CharacterLimited
				? self::createBestFittingText($type->characterLimit)
				: new DataType\VarChar(255),
			'float' => $type instanceof Type\Sized
				? self::createBestFittingFloat($type->bytes, $type instanceof Type\Unsigned)
				: new DataType\Float_(isUnsigned: $type instanceof Type\Unsigned),
			'int' => $type instanceof Type\Sized
				? self::createBestFittingInt($type->bytes, $type instanceof Type\Unsigned)
				: new DataType\Int_(isUnsigned: $type instanceof Type\Unsigned),
			'bool' => new DataType\TinyInt(1),
			DateTime::class, DateTimeImmutable::class, DateTimeInterface::class
				=> new DataType\DateTime,
			BlobValue::class => $type instanceof Type\ByteLimited
				? self::createBestFittingBlob($type->byteLimit)
				: DataType\Blob::instance(),
			Entity\SequentialValue::class => new DataType\Int_(isUnsigned: true),
			Entity\UniqueIdentifier::class => DataType\Uuid::instance(),
			default => self::fromClass($type, $serializeType)
		};
	}

	/** @param class-string $serializeType */
	private static function fromClass(Type $type, string $serializeType): DataType {
		if (\is_a($serializeType, BackedEnum::class, allow_string: true)) {
			return DataType\Enum::fromBackedEnum($serializeType);
		}

		throw new LogicException("Unexpected type `$serializeType`, can't be converted to a MariaDB type");
	}

	/** @param int<0,max> $charLimit */
	private static function createBestFittingText(int $charLimit): DataType&DataType\Textual {
		if ($charLimit > DataType\MediumText::LIMIT) {
			return DataType\LongText::instance();
		} else if ($charLimit > DataType\VarChar::LIMIT) {
			return DataType\MediumText::instance();
		}
		return new DataType\VarChar($charLimit);
	}

	private static function createBestFittingFloat(int $bytes, bool $isUnsigned): DataType&DataType\Numeric {
		return $bytes > DataType\Float_::BYTES
			? new DataType\Double_(isUnsigned: $isUnsigned)
			: new DataType\Float_(isUnsigned: $isUnsigned);
	}

	private static function createBestFittingInt(int $bytes, bool $isUnsigned): DataType&DataType\Numeric {
		if ($bytes > DataType\Int_::BYTES) {
			return new DataType\BigInt(isUnsigned: $isUnsigned);
		} else if ($bytes > DataType\MediumInt::BYTES) {
			return new DataType\Int_(isUnsigned: $isUnsigned);
		} else if ($bytes > DataType\SmallInt::BYTES) {
			return new DataType\MediumInt(isUnsigned: $isUnsigned);
		} else if ($bytes > DataType\TinyInt::BYTES) {
			return new DataType\SmallInt(isUnsigned: $isUnsigned);
		}
		return new DataType\TinyInt(isUnsigned: $isUnsigned);
	}

	private static function createBestFittingBlob(int $byteLimit): DataType&DataType\Binary {
		if ($byteLimit > DataType\MediumBlob::LIMIT) {
			return DataType\LongBlob::instance();
		} else if ($byteLimit > DataType\Blob::LIMIT) {
			return DataType\MediumBlob::instance();
		} else if ($byteLimit > DataType\TinyBlob::LIMIT) {
			return DataType\Blob::instance();
		}
		return DataType\TinyBlob::instance();
	}

	private function __construct() { }

}
