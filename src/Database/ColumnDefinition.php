<?php

namespace Slendium\OcdMariaDb\Database;

use Override;

use Slendium\Ocd\Entity;
use Slendium\Ocd\Schema;
use Slendium\Ocd\Schema\Field;
use Slendium\Ocd\Schema\Type;
use Slendium\Ocd\Schema\TypeInfo;

use Slendium\OcdMariaDb\Common\Diff;
use Slendium\OcdMariaDb\Common\LiteralSqlizer;
use Slendium\OcdMariaDb\InfoSchema;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class ColumnDefinition implements Diff\Identifiable {

	#[Override]
	public string $identity {
		get => $this->name;
	}

	public static function fromInfoSchema(InfoSchema\Column $column, bool $isJson): self {
		return new self(
			name: $column->COLUMN_NAME,
			type: DataTypeFactory::createFromInfoSchema($column),
			isNullable: $column->isNullable,
			defaultValue: $column->COLUMN_DEFAULT,
			isAutoIncrement: $column->isAutoIncrement,
			isJson: $isJson,
		);
	}

	/** @return list<self> */
	public static function createListFromSchema(Schema $schema): array {
		return \array_values(\array_map(self::fromField(...), $schema->fields));
	}

	public static function fromField(Field $field): self {
		$serializeType = TypeInfo::getSerializeType($field->type::class);

		return new self(
			name: $field->name,
			type: DataTypeFactory::createFromSchemaType($field->type),
			isNullable: $field->isNullable,
			defaultValue: $field->defaultValue,
			isUnsigned: $field->type instanceof Type\Unsigned,
			isAutoIncrement: $serializeType === Entity\SequentialValue::class,
			isJson: $serializeType === 'array',
		);
	}

	public function __construct(

		/** @var non-empty-string */
		public readonly string $name,

		public readonly DataType $type,

		public readonly bool $isNullable = false,

		public readonly mixed $defaultValue = null,

		public readonly bool $isUnsigned = false,

		public readonly bool $isAutoIncrement = false,

		public readonly bool $isJson = false,

	) { }

	/** @return non-empty-string */
	public function toModifySql(): string {
		return 'MODIFY '.$this->toCreateSql();
	}

	/** @return non-empty-string */
	public function toCreateSql(): string {
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
