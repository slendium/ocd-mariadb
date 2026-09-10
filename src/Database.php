<?php

namespace Slendium\OcdMariaDb;

use Override;
use PDO;
use SensitiveParameter;

use Slendium\Ocd\Collection as ICollection;
use Slendium\Ocd\Database as IDatabase;
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
	public function enforceSchema(string $collection, Schema $schema): IDatabase\UpgradeCommand {
		return new Database\EnforceSchemaCommand($this->pdo, $collection, $schema);
	}

	#[Override]
	public function listCollections(): iterable {
		foreach ($this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN, column: 0) as $name) { // @phpstan-ignore method.nonObject
			/** @var non-empty-string $name */
			yield $name => new Collection($this->pdo, $name);
		}
	}

	#[Override]
	public function getCollection(string $collection): ICollection {
		return new Collection($this->pdo, $collection);
	}

	#[Override]
	public function deleteCollection(string $collection): ICollection {
		$this->pdo->query("DROP TABLE $collection");
		throw new \Exception('Not implemented');
	}

}
