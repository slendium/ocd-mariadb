<?php

namespace Slendium\OcdMariaDb;

use Override;
use PDO;
use SensitiveParameter;

use Slendium\Ocd\Collection;
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
	) {
		$this->pdo = PDO::connect("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
	}

	#[Override]
	public function enforceSchema(string $collection, Schema $schema): IDatabase\UpgradeCommand {
		return new Database\EnforceSchemaCommand($this->pdo, $collection, $schema);
	}

	#[Override]
	public function listCollections(): iterable {
		throw new \Exception('Not implemented');
	}

	#[Override]
	public function getCollection(string $collection): Collection {
		throw new \Exception('Not implemented');
	}

	#[Override]
	public function deleteCollection(string $collection): Collection {
		$this->pdo->query("DROP TABLE $collection");
		throw new \Exception('Not implemented');
	}

}
