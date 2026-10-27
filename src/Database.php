<?php

namespace Slendium\OcdMariaDb;

use LogicException;
use Override;
use PDO;
use SensitiveParameter;

use Slendium\Ocd\Collection as ICollection;
use Slendium\Ocd\Database as IDatabase;
use Slendium\Ocd\Database\Feature;
use Slendium\Ocd\Schema;

/**
 * @since 1.0
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final class Database implements IDatabase {

	private readonly PDO $pdo;

	/** @since 1.0 */
	public function __construct(
		string $host,
		string $database,
		#[SensitiveParameter] ?string $username = null,
		#[SensitiveParameter] ?string $password = null,
		?int $port = null,
		string $charset = 'utf8mb4',
	) {
		$dsn = "mysql:host=$host;dbname=$database;charset=$charset";
		if ($port !== null) {
			$dsn .= ";port=$port";
		}
		$this->pdo = PDO::connect($dsn, $username, $password, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false,
		]);
	}

	#[Override]
	public function supports(Feature $feature): bool {
		return match($feature) {
			Feature::SchemaEnforcement => true,
			Feature::CustomPrimary => true,
			default => false,
		};
	}

	#[Override]
	public function applySchema(string $collection, Schema $schema): IDatabase\UpgradeCommand {
		return new Database\ApplySchemaCommand($this->pdo, $collection, $schema);
	}

	#[Override]
	public function listCollections(): array {
		$out = [ ];
		foreach ($this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN, column: 0) as $name) { // @phpstan-ignore method.nonObject
			/** @var non-empty-string $name */
			$out[] = $name;
		}
		return $out;
	}

	#[Override]
	public function hasCollection(string $collection): bool {
		self::throwIfInvalidCollectionName($collection);

		$stmt = $this->pdo->query("SHOW TABLES LIKE '$collection'");
		return $stmt !== false && $stmt->fetchColumn() !== false;
	}

	#[Override]
	public function getCollection(string $collection): ICollection {
		self::throwIfInvalidCollectionName($collection);

		return new Collection($this->pdo, $collection);
	}

	#[Override]
	public function deleteCollection(string $collection): void {
		self::throwIfInvalidCollectionName($collection);

		$this->pdo->query("DROP TABLE $collection");
	}

	private static function throwIfInvalidCollectionName(string $name): void {
		for ($i = 0; $i < \strlen($name); $i += 1) {
			if ($name[$i] === "'" || $name[$i] === "`") {
				throw new LogicException("Illegal character {$name[$i]} in table name: $name");
			}
		}
	}

}
