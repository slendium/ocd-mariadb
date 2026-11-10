<?php

namespace Slendium\OcdMariaDb\InfoSchema;

use PDO;

use Slendium\OcdMariaDb\Common\PartialObject;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class InfoSchemaObject {

	/** @return list<Column> */
	public static function fetchTableColumns(PDO $pdo, string $table): array {
		return self::fetchAllForTable($pdo, Column::class, [
			'SELECT * FROM INFORMATION_SCHEMA.COLUMNS '
				.'WHERE TABLE_SCHEMA = SCHEMA() AND TABLE_NAME = :table',
			[ 'table' => $table ]
		]);
	}

	/** @return list<CheckConstraint> */
	public static function fetchTableCheckConstraints(PDO $pdo, string $table): array {
		return self::fetchAllForTable($pdo, CheckConstraint::class, [
			'SELECT * FROM INFORMATION_SCHEMA.CHECK_CONSTRAINTS '
				.'WHERE CONSTRAINT_SCHEMA = SCHEMA() AND TABLE_NAME = :table',
			[ 'table' => $table ]
		]);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $targetClass
	 * @param array{ 0: string, 1: array<string,mixed> } $query
	 * @return list<T>
	 */
	private static function fetchAllForTable(PDO $pdo, string $targetClass, array $query): array {
		$stmt = $pdo->prepare($query[0]);
		$stmt->execute($query[1]);

		$result = [ ];
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			/** @var array<mixed> $row */
			$result[] = PartialObject::createFromRow($targetClass, $row);
		}
		return $result;
	}

	private function __construct() { }

}
