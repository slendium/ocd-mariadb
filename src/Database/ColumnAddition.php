<?php

namespace Slendium\OcdMariaDb\Database;

/**
 * @internal
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class ColumnAddition {

	public function __construct(

		public ColumnDefinition $column,

		/** @var ?non-empty-string */
		public ?string $after,

	) { }

	/** @return non-empty-string */
	public function toAddColumnSql(): string {
		$spec = 'ADD COLUMN '.$this->column->toCreateSql();
		$spec .= $this->after !== null
			? " AFTER `{$this->after}`"
			: ' FIRST';
		return $spec;
	}

}
