<?php

namespace Slendium\OcdMariaDb\InfoSchema;

use PDO;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class CheckConstraint {

	const LEVEL_COLUMN = 'Column';

	const LEVEL_TABLE = 'Table';

	public bool $isJsonColumn {
		get => $this->CHECK_CLAUSE === "json_valid(`{$this->CONSTRAINT_NAME}`)"
			&& $this->LEVEL === self::LEVEL_COLUMN;
	}

	/** @return list<self> */
	public static function fetchForTable(PDO $pdo, string $table): array {
		return InfoSchemaObject::fetchTableCheckConstraints($pdo, $table);
	}

	private function __construct(

		public readonly string $CONSTRAINT_NAME,

		/** @var self::LEVEL_* */
		public readonly string $LEVEL,

		public readonly string $CHECK_CLAUSE,

	) { }

}
