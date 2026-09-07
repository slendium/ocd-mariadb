<?php

namespace Slendium\OcdMariaDb\Database;

use Slendium\Ocd\Schema;
use Slendium\Ocd\Schema\Field;
use Slendium\Ocd\Schema\IdGenerator;
use Slendium\Ocd\Schema\IdOptions;
use Slendium\Ocd\Schema\TypeInfo;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class ColumnDefinition {

	/** @param array<mixed> $row */
	public static function fromInformationSchemaRow(array $row): self {
		return new self(
			name: $row['COLUMN_NAME'], // @phpstan-ignore argument.type
			type: DataType::from($row['DATA_TYPE']), // @phpstan-ignore argument.type
			isNullable: $row['IS_NULLABLE'] === 'YES',
			characterCount: $row['CHARACTER_MAXIMUM_LENGTH'] ?? null, // @phpstan-ignore argument.type
			defaultValue: $row['COLUMN_DEFAULT'],
			isPrimaryKey: $row['COLUMN_KEY'] === 'PRI',
			isAutoIncrement: \str_contains($row['EXTRA'], 'auto_increment') // @phpstan-ignore argument.type
		);
	}

	/** @return list<self> */
	public static function createListFromSchema(Schema $schema): array {
		$out = [ self::fromIdOptions($schema->idOptions) ];
		foreach ($schema->fields as $field) {
			$out[] = self::fromField($field);
		}
		return $out;
	}

	public static function fromField(Field $field): self {
		return new self(
			name: $field->name,
			type: DataType::fromStorageClass(TypeInfo::getStorageClass(\get_class($field->type))),
			isNullable: $field->nullable,
			defaultValue: $field->defaultValue
		);
	}

	public static function fromIdOptions(IdOptions $idOptions): self {
		return match($idOptions->generator) {
			IdGenerator::None => new self('id', DataType::VarChar, characterCount: 255, isPrimaryKey: true),
			IdGenerator::Sequence => new self('id', DataType::Int, isUnsigned: true, isPrimaryKey: true, isAutoIncrement: true),
			IdGenerator::UniqueIdentifier => new self('id', DataType::Uuid, isPrimaryKey: true)
		};
	}

	public function __construct(

		/** @var non-empty-string */
		public string $name,

		public DataType $type,

		public bool $isNullable = false,

		public mixed $defaultValue = null,

		public ?int $characterCount = null,

		public bool $isUnsigned = false,

		public bool $isPrimaryKey = false,

		public bool $isAutoIncrement = false,

	) { }

	/** @return non-empty-string */
	public function toCreateSql(): string {
		$out = $this->toBaseSql();
		if ($this->isPrimaryKey) {
			$out .= ' PRIMARY KEY';
		}
		return $out;
	}

	/**
	 * @param ?int $characterCount Allows overriding the target character count of varchars to prevent
	 *  truncation of data
	 * @return non-empty-string
	 */
	public function toModifySql(?int $characterCount = null): string {
		return 'MODIFY '.$this->toBaseSql($characterCount);
	}

	/** @return non-empty-string */
	private function toBaseSql(?int $characterCount = null): string {
		$out = "`{$this->name}` ".$this->type->toSql($characterCount ?? $this->characterCount);
		if ($this->isUnsigned) {
			$out .= ' UNSIGNED';
		}
		$out .= $this->isNullable
			? ' NULL'
			: ' NOT NULL';
		if (\is_scalar($this->defaultValue)) {
			$out .= " DEFAULT '{$this->defaultValue}'";
		}
		if ($this->isAutoIncrement) {
			$out .= ' AUTO_INCREMENT';
		}
		return $out;
	}

}
