<?php

namespace Slendium\OcdMariaDb\Common\Diff;

use Slendium\OcdMariaDb\Common\Diff;

/**
 * @internal
 * @template TItem of Identifiable
 * @template TDiff of ObjectDiff
 * @author C. Fahner
 * @copyright Slendium 2026
 */
final readonly class ListDifference {

	/**
	 * @template TInputItem of Identifiable
	 * @template TDiffResult of ObjectDiff<TInputItem>
	 * @param iterable<TInputItem> $before
	 * @param iterable<TInputItem> $after
	 * @param class-string<TDiffResult> $diffClass
	 * @return ?self<TInputItem,TDiffResult>
	 */
	public static function create(iterable $before, iterable $after, string $diffClass): ?self {
		$beforeMap = [ ];
		$deletions = [ ];

		foreach ($before as $item) {
			$beforeMap[$item->identity] = $item;
			$deletions[$item->identity] = true;
		}

		$additions = [ ];
		$modifications = [ ];

		$previousIdentity = null;
		foreach ($after as $item) {
			if (isset($beforeMap[$item->identity])) {
				unset($deletions[$item->identity]);
				$diff = [ $diffClass, 'diff' ]($beforeMap[$item->identity], $item);
				if ($diff !== null) {
					$modifications[] = $diff;
				}
			} else {
				$additions[] = new ListAddition($item, $previousIdentity);
			}
			$previousIdentity = $item->identity;
		}

		return \count($additions) > 0 || \count($modifications) > 0 || \count($deletions) > 0
			? new self($additions, $modifications, \array_keys($deletions))
			: null;
	}

	private function __construct(

		/** @var list<ListAddition<TItem>> */
		public array $additions,

		/** @var list<TDiff> */
		public array $modifications,

		/** @var list<string> */
		public array $deletions,

	) { }

}
