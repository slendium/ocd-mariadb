<?php

namespace Slendium\OcdMariaDb\Collection;

use Override;
use PDO;

use Slendium\Ocd\Collection\WriteCommand;
use Slendium\Ocd\Collection\WriteOptions;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
abstract class BaseWriteCommand implements WriteCommand {

	#[Override]
	public readonly WriteOptions $writeOptions;

	public function __construct(

		protected readonly PDO $pdo,

		/** @var non-empty-string */
		protected readonly string $table,

	) {
		$this->writeOptions = new WriteOptions;
	}

}
