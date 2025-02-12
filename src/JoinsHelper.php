<?php

namespace Kirschbaum\PowerJoins;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;

class JoinsHelper
{
	public static array $instances = [];
	
	protected function __construct()
	{
	}
	
	public static function make(): static
	{
		$objects = array_map(fn($object) => spl_object_id($object), func_get_args());
		
		return static::$instances[implode('-', $objects)] ??= new self();
	}
	
	/**
	 * Cache to not join the same relationship twice.
	 */
	private array $joinRelationshipCache = [];
	
	/**
	 * Join method map.
	 */
	public static $joinMethodsMap = [
		'join' => 'powerJoin',
		'leftJoin' => 'leftPowerJoin',
		'rightJoin' => 'rightPowerJoin',
	];
	
	/**
	 * Cache to determine which query model belongs to which query.
	 * This is used to determine if a query is a clone of another
	 * query and therefore if we should refresh the model in it.
	 *
	 * The keys are the spl object IDs of the model, and the
	 * value is the spl object ID of the associated query.
	 */
	public static array $modelQueryDictionary = [];
	
	public static array $beforeQueryCallbacks = [];
	
	public static function ensureModelIsUniqueToQuery($query): void
	{
		$originalModel = $query->getModel();
		
		$originalModelSplObjectId = spl_object_id($originalModel);
		$querySplObjectId = spl_object_id($query);
		
		if ( isset(static::$modelQueryDictionary[$originalModelSplObjectId]) && static::$modelQueryDictionary[$originalModelSplObjectId] !== $querySplObjectId ) {
			// If the model is already associated with another query, we need to clone the model.
			// This can happen if a certain query, *before having interacted with the library
			// `joinRelationship()` method, was cloned by previous code.
			$query->setModel($model = new ($query->getModel()));
			
			// If there is a `JoinsHelper` with a cache associated with the old model,
			// we will copy the cache over to the new fresh model clone added to it.
			$originalJoinsHelper = JoinsHelper::make($originalModel);
			$joinsHelper = JoinsHelper::make($model);
			
			static::$modelQueryDictionary[spl_object_id($model)] = $querySplObjectId;
			
			foreach ($originalJoinsHelper->joinRelationshipCache[$originalModelSplObjectId]??[] as $relation => $value) {
				$joinsHelper->markRelationshipAsAlreadyJoined($model, $relation);
			}
			
			//			foreach ($query->getQuery()->beforeQueryCallbacks as $key => $beforeQueryCallback) {
			//				/** @var Closure $beforeQueryCallback */
			//				$query->getQuery()->beforeQueryCallbacks[$key] = $beforeQueryCallback->bindTo($query);
			//			}
			
			// TODO: we will need to update any `beforeQueryCallbacks` to bind the new `$query` to them. Or not here?
		} else {
			static::$modelQueryDictionary[$originalModelSplObjectId] = $querySplObjectId;
		}
		
		if ( method_exists($query, 'onClone') ) {
			// Method added in Laravel ^11.42.
			$query->onClone(static function (Builder $query) {
				$originalModel = $query->getModel();
				$originalJoinsHelper = JoinsHelper::make($originalModel);
				
				$query->setModel($model = new $originalModel);
				
				foreach ($query->getQuery()->beforeQueryCallbacks as $key => $beforeQueryCallback) {
					if ( in_array($beforeQueryCallback, static::$beforeQueryCallbacks, true) ) {
						/** @var Closure $beforeQueryCallback */
						$query->getQuery()->beforeQueryCallbacks[$key] = $beforeQueryCallback->bindTo($query);
					}
				}
				
				$joinsHelper = JoinsHelper::make($model);
				
				foreach ($originalJoinsHelper->joinRelationshipCache[spl_object_id($originalModel)]??[] as $relation => $value) {
					$joinsHelper->markRelationshipAsAlreadyJoined($model, $relation);
				}
			});
		}
	}
	
	public static function clearCacheBeforeQuery($query): void
	{
		$query->getQuery()->beforeQuery($beforeQueryCallback = (function () {
			JoinsHelper::make($this->getModel())->clear($this->getModel());
		})->bindTo($query));
		
		static::$beforeQueryCallbacks[] = $beforeQueryCallback;
	}
	
	/**
	 * Format the join callback.
	 */
	public function formatJoinCallback($callback)
	{
		if ( is_string($callback) ) {
			return function ($join) use ($callback) {
				$join->as($callback);
			};
		}
		
		return $callback;
	}
	
	public function generateAliasForRelationship(Relation $relation, string $relationName): array|string
	{
		if ( $relation instanceof BelongsToMany || $relation instanceof HasManyThrough ) {
			return [
				md5($relationName . 'table1' . time()),
				md5($relationName . 'table2' . time()),
			];
		}
		
		return md5($relationName . time());
	}
	
	/**
	 * Get the join alias name from all the different options.
	 */
	public function getAliasName(bool $useAlias, Relation $relation, string $relationName, string $tableName, $callback): string|array|null
	{
		if ( $callback ) {
			if ( is_callable($callback) ) {
				$fakeJoinCallback = new FakeJoinCallback($relation->getBaseQuery(), 'inner', $tableName);
				$callback($fakeJoinCallback);
				
				if ( $fakeJoinCallback->getAlias() ) {
					return $fakeJoinCallback->getAlias();
				}
			}
			
			if ( is_array($callback) && isset($callback[$tableName]) ) {
				$fakeJoinCallback = new FakeJoinCallback($relation->getBaseQuery(), 'inner', $tableName);
				$callback[$tableName]($fakeJoinCallback);
				
				if ( $fakeJoinCallback->getAlias() ) {
					return $fakeJoinCallback->getAlias();
				}
			}
		}
		
		return $useAlias ? $this->generateAliasForRelationship($relation, $relationName) : null;
	}
	
	/**
	 * Checks if the relationship was already joined.
	 */
	public function relationshipAlreadyJoined($model, string $relation): bool
	{
		return isset($this->joinRelationshipCache[spl_object_id($model)][$relation]);
	}
	
	/**
	 * Marks the relationship as already joined.
	 */
	public function markRelationshipAsAlreadyJoined($model, string $relation): void
	{
		$this->joinRelationshipCache[spl_object_id($model)][$relation] = true;
	}
	
	public function clear($model): void
	{
		unset($this->joinRelationshipCache[spl_object_id($model)]);
	}
}
