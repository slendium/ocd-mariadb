<?php

namespace Slendium\OcdMariaDb\Database;

use Slendium\Ocd\Entity;
use Slendium\Ocd\Schema;
use Slendium\Ocd\Schema\Field;
use Slendium\Ocd\Schema\Type;
use Slendium\Ocd\Schema\TypeInfo;

use Slendium\OcdMariaDb\Common\LiteralSqlizer;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class ColumnDefinition {

	/**
	 * Creates the column definition from a `information_schema.columns` row.
	 * @param array<mixed> $row
	 */
	public static function fromInformationSchemaRow(array $row, bool $isJson): self {
		return new self(
			name: $row['COLUMN_NAME'], // @phpstan-ignore argument.type
			type: DataTypeFactory::createFromInformationSchemaRow($row), // @phpstan-ignore argument.type
			isNullable: $row['IS_NULLABLE'] === 'YES',
			defaultValue: $row['COLUMN_DEFAULT'],
			isPrimaryKey: $row['COLUMN_KEY'] === 'PRI',
			isAutoIncrement: \str_contains($row['EXTRA'], 'auto_increment'), // @phpstan-ignore argument.type
			isJson: $isJson,
		);
	}

	/** @return list<self> */
	public static function createListFromSchema(Schema $schema): array {
		$defs = [ ];
		foreach ($schema->fields as $field) {
			$defs[] = self::fromField($field, \in_array($field->name, $schema->primaries));
		}
		return $defs;
	}

	public static function fromField(Field $field, bool $isPrimary = false): self {
		$serializeType = TypeInfo::getSerializeType($field->type::class);

		return new self(
			name: $field->name,
			type: DataTypeFactory::createFromSchemaType($field->type),
			isNullable: $field->isNullable,
			defaultValue: $field->defaultValue,
			isUnsigned: $field->type instanceof Type\Unsigned,
			isPrimaryKey: $isPrimary,
			isAutoIncrement: $serializeType === Entity\SequentialValue::class,
			isJson: $serializeType === 'array',
		);
	}

	public function __construct(

		/** @var non-empty-string */
		public string $name,

		public DataType $type,

		public bool $isNullable = false,

		public mixed $defaultValue = null,

		public bool $isUnsigned = false,

		public bool $isPrimaryKey = false,

		public bool $isAutoIncrement = false,

		public bool $isJson = false,

	) { }

	/** @return non-empty-string */
	public function toCreateSql(): string {
		$out = $this->toBaseSql();
		if ($this->isPrimaryKey) {
			$out .= ' PRIMARY KEY';
		}
		return $out;
	}

	/** @return non-empty-string */
	public function toModifySql(): string {
		return 'MODIFY '.$this->toBaseSql();
	}

	/** @return non-empty-string */
	private function toBaseSql(): string {
		$out = "`{$this->name}`";

		$out .= $this->isJson
			? ' JSON'
			: $this->type->toSql();

		if ($this->isUnsigned) {
			$out .= ' UNSIGNED';
		}

		$out .= $this->isNullable
			? ' NULL'
			: ' NOT NULL';

		if (\is_scalar($this->defaultValue)) {
			$out .= ' DEFAULT '.LiteralSqlizer::formatValue($this->defaultValue);
		} else if ($this->type instanceof DataType\Uuid) {
			$out .= ' DEFAULT UUID()';
		}

		if ($this->isAutoIncrement) {
			$out .= ' AUTO_INCREMENT';
		}

		if ($this->isJson) {
			$out .= " CHECK (JSON_VALID(`{$this->name}`))";
		}

		return $out;
	}

}
