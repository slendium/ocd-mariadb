<?php

namespace Slendium\OcdMariaDb\InfoSchema;

use PDO;
use RuntimeException;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class Column {

	public bool $isPrimary {
		get => $this->COLUMN_KEY === 'PRI';
	}

	public bool $isNullable {
		get => $this->IS_NULLABLE === '1';
	}

	/** @var ?int<0,65532> */
	public ?int $characterLimit {
		get => $this->get_characterLimit();
	}

	/** @var ?int<0,6> */
	public ?int $dateTimePrecision {
		get => $this->get_dateTimePrecision();
	}

	public bool $isAutoIncrement {
		get => \str_contains($this->EXTRA, 'auto_increment');
	}

	/** @return list<self> */
	public static function fetchForTable(PDO $pdo, string $table): array {
		return InfoSchemaObject::fetchTableColumns($pdo, $table);
	}

	private function __construct(

		/**
		 * The column [identifier](https://mariadb.com/docs/server/reference/sql-structure/sql-language-structure/identifier-names).
		 *
		 * At the time of writing the [documentation](https://archive.ph/r2Qtv) does not explicitly
		 * make it clear if names may be empty.
		 * MariaDB v11.8.6 does seem to reject empty names during table creation, so the property type
		 * here is assumed to be `non-empty-string`.
		 *
		 * @var non-empty-string
		 */
		public readonly string $COLUMN_NAME,

		/** Can be a literal (which is wrapped in single quotes), an expression or NULL. */
		public readonly string $COLUMN_DEFAULT,

		/** @var '0'|'1' */
		public readonly string $IS_NULLABLE,

		/**
		 * Contains the plain type name, such as `varchar` when COLUMN_TYPE is `varchar(200)`.
		 * @var non-empty-string
		 */
		public readonly string $DATA_TYPE,

		/**
		 * Contains the full parameterized type declaration, such as `float(10,5)`.
		 * @var non-empty-string
		 */
		public readonly string $COLUMN_TYPE,

		/** @see $characterLimit */
		public readonly string $CHARACTER_MAXIMUM_LENGTH,

		/** @see $dateTimePrecision */
		public readonly string $DATETIME_PRECISION,

		/** @var 'PRI'|'UNI'|'MUL'|'' */
		public readonly string $COLUMN_KEY,

		/** Contains eg. 'auto_increment' */
		public readonly string $EXTRA,

	) { }

	/** @return ?int<0,65532> */
	private function get_characterLimit(): ?int {
		if (\is_numeric($this->CHARACTER_MAXIMUM_LENGTH)) {
			$limit = (int)$this->CHARACTER_MAXIMUM_LENGTH;
			return $limit >= 0 && $limit <= 65532
				? $limit
				: throw new RuntimeException('Expected CHARACTER_MAXIMUM_LENGTH to be in range 0..65532');
		}

		return $this->CHARACTER_MAXIMUM_LENGTH === ''
			? null
			: throw new RuntimeException("Unexpected CHARACTER_MAXIMUM_LENGTH value `{$this->CHARACTER_MAXIMUM_LENGTH}`");
	}

	/** @return ?int<0,6> */
	private function get_dateTimePrecision(): ?int {
		if (\is_numeric($this->DATETIME_PRECISION)) {
			$precision = (int)$this->DATETIME_PRECISION;
			return $precision >= 0 && $precision <= 6
				? $precision
				: throw new RuntimeException('Expected DATETIME_PRECISION to be in range 0..6');
		}

		return $this->DATETIME_PRECISION === ''
			? null
			: throw new RuntimeException("Unexpected DATETIME_PRECISION value `{$this->DATETIME_PRECISION}`");
	}

}
