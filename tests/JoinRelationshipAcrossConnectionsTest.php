<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\Tests\Models\CrossConnection\Article;
use Kirschbaum\PowerJoins\Tests\Models\CrossConnection\Author;
use Kirschbaum\PowerJoins\Tests\Models\CrossConnection\Comment;
use Kirschbaum\PowerJoins\Tests\Models\CrossConnection\Profile;
use Kirschbaum\PowerJoins\Tests\Models\CrossConnection\Sticker;

class JoinRelationshipAcrossConnectionsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.connections.primary', [
            'driver' => 'mysql',
            'database' => 'primary_db',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        $app['config']->set('database.connections.secondary', [
            'driver' => 'mysql',
            'database' => 'secondary_db',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Basic join types — HasMany / BelongsTo / HasOne
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_inner_join_has_many_uses_related_model_connection_prefix()
    {
        $query = Author::query()->joinRelationship('articles')->toSql();

        $this->assertQueryContains('from "authors"', $query);
        $this->assertQueryContains(
            'inner join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
        $this->assertQueryNotContains('join "articles"', $query);
    }

    /** @test */
    public function test_left_join_has_many_uses_related_model_connection_prefix()
    {
        $query = Author::query()->leftJoinRelationship('articles')->toSql();

        $this->assertQueryContains(
            'left join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
    }

    /** @test */
    public function test_right_join_has_many_uses_related_model_connection_prefix()
    {
        $query = Author::query()->rightJoinRelationship('articles')->toSql();

        $this->assertQueryContains(
            'right join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
    }

    /** @test */
    public function test_belongs_to_uses_related_model_connection_prefix()
    {
        $query = Article::query()->joinRelationship('author')->toSql();

        $this->assertQueryContains('from "articles"', $query);
        $this->assertQueryContains(
            'inner join "primary_db"."authors" on "articles"."author_id" = "primary_db"."authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"secondary_db"."authors"', $query);
    }

    /** @test */
    public function test_has_one_uses_related_model_connection_prefix()
    {
        $query = Author::query()->joinRelationship('profile')->toSql();

        $this->assertQueryContains(
            'inner join "secondary_db"."profiles" on "secondary_db"."profiles"."author_id" = "authors"."id"',
            $query
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Nested relationships (cross-connection at multiple levels)
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_nested_has_many_across_connections()
    {
        // Author (testing) -> articles (secondary_db) -> comments (secondary_db)
        $query = Author::query()->joinRelationship('articles.comments')->toSql();

        $this->assertQueryContains(
            'inner join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "secondary_db"."comments" on "secondary_db"."comments"."article_id" = "secondary_db"."articles"."id"',
            $query
        );
    }

    /** @test */
    public function test_nested_belongs_to_across_connections()
    {
        // Comment (secondary_db) -> article (secondary_db) -> author (primary_db)
        // Comment->Article is same-connection (no qualifier); Article->Author is cross-connection
        // and primary is in qualifyWithDatabase, so authors is qualified with primary_db.
        $query = Comment::query()->joinRelationship('article.author')->toSql();

        $this->assertQueryContains(
            'inner join "articles" on "comments"."article_id" = "articles"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "primary_db"."authors" on "articles"."author_id" = "primary_db"."authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"secondary_db"."authors"', $query);
    }

    /** @test */
    public function test_nested_zig_zag_across_connections()
    {
        // Profile (secondary_db) -> author (primary_db) -> articles (secondary_db)
        // Cross-connection is measured from the base (Profile, secondary_db):
        // Author is cross-connection and primary is qualified → primary_db.authors.
        // Articles is same-connection as base (secondary_db) — no DB qualifier.
        $query = Profile::query()->joinRelationship('author.articles')->toSql();

        $this->assertQueryContains(
            'inner join "primary_db"."authors" on "profiles"."author_id" = "primary_db"."authors"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "articles" on "articles"."author_id" = "primary_db"."authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"secondary_db"."articles"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | BelongsToMany / MorphMany / MorphToMany
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_belongs_to_many_uses_parent_connection_for_pivot_and_related_connection_for_related_table()
    {
        $query = Author::query()->joinRelationship('tags')->toSql();

        // Pivot table uses parent's connection (same as base query, no DB qualifier needed)
        $this->assertQueryContains(
            'inner join "author_tag" on "author_tag"."author_id" = "authors"."id"',
            $query
        );
        // Related table uses related model's connection
        $this->assertQueryContains(
            'inner join "secondary_db"."tags" on "secondary_db"."tags"."id" = "author_tag"."tag_id"',
            $query
        );
    }

    /** @test */
    public function test_morph_many_uses_related_model_connection_prefix()
    {
        $query = Author::query()->joinRelationship('stickers')->toSql();

        $this->assertQueryContains(
            'inner join "secondary_db"."stickers" on "secondary_db"."stickers"."stickerable_id" = "authors"."id"',
            $query
        );
    }

    /** @test */
    public function test_morph_to_many_uses_parent_connection_for_pivot_and_related_connection_for_related_table()
    {
        $query = Author::query()->joinRelationship('labels')->toSql();

        // Pivot table uses parent's connection (same as base query, no DB qualifier needed)
        $this->assertQueryContains('"labelables"', $query);
        $this->assertQueryNotContains('"secondary_db"."labelables"', $query);
        // Related table uses related model's connection
        $this->assertQueryContains('"secondary_db"."labels"', $query);
    }

    /** @test */
    public function test_morph_to_many_morph_type_condition_uses_parent_qualifier()
    {
        $query = Author::query()->joinRelationship('labels')->toSql();

        // The morph type discriminator on the pivot uses parent's connection (no DB qualifier)
        $this->assertQueryContains('"labelables"."labelable_type"', $query);
        $this->assertQueryNotContains('"secondary_db"."labelables"."labelable_type"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | HasManyThrough
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_has_many_through_uses_related_connection_for_through_and_far_tables()
    {
        // Author (testing) hasManyThrough Comment (secondary_db) via Article (secondary_db)
        $query = Author::query()->joinRelationship('commentsThroughArticles')->toSql();

        $this->assertQueryContains(
            'inner join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "secondary_db"."comments" on "secondary_db"."comments"."article_id" = "secondary_db"."articles"."id"',
            $query
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes — deleted_at clauses must also use the correct qualifier
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_soft_deletes_deleted_at_clause_uses_related_connection_prefix()
    {
        $query = Author::query()->joinRelationship('articles')->toSql();

        $this->assertQueryContains('"secondary_db"."articles"."deleted_at" is null', $query);
    }

    /** @test */
    public function test_soft_deletes_base_model_uses_its_own_connection_prefix()
    {
        $query = Article::query()->joinRelationship('author')->toSql();

        $this->assertQueryContains('"primary_db"."authors"."deleted_at" is null', $query);
        $this->assertQueryNotContains('"secondary_db"."authors"."deleted_at"', $query);
    }

    /** @test */
    public function test_with_trashed_relationship_does_not_add_deleted_at_clause()
    {
        $query = Author::query()->joinRelationship('articlesWithTrashed')->toSql();

        $this->assertQueryContains(
            'inner join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"secondary_db"."articles"."deleted_at"', $query);
    }

    /** @test */
    public function test_belongs_to_with_trashed_cross_connection_does_not_add_deleted_at_clause()
    {
        // Inverse path: Article (secondary) belongsTo Author (primary) withTrashed.
        // The primary_db qualifier must appear on the join but no deleted_at clause.
        $query = Article::query()->joinRelationship('authorWithTrashed')->toSql();

        $this->assertQueryContains(
            'inner join "primary_db"."authors" on "articles"."author_id" = "primary_db"."authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"primary_db"."authors"."deleted_at"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Extra conditions / scopes
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_extra_where_conditions_on_related_use_correct_prefix()
    {
        $query = Author::query()->joinRelationship('publishedArticles')->toSql();

        $this->assertQueryContains(
            'inner join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
        $this->assertQueryContains('"secondary_db"."articles"."published" = ?', $query);
    }

    /** @test */
    public function test_callback_closure_on_join_uses_correct_prefix()
    {
        $query = Author::query()->joinRelationship('articles', function ($join) {
            $join->where('articles.published', true);
        })->toSql();

        $this->assertQueryContains(
            'inner join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
        $this->assertQueryContains('"secondary_db"."articles"."published"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Aliases
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_join_with_alias_still_uses_related_connection_for_real_table()
    {
        $query = Author::query()->joinRelationship('articles', fn ($join) => $join->as('a'))->toSql();

        $this->assertQueryContains('"secondary_db"."articles" as "a"', $query);
        $this->assertQueryContains('"a"."author_id" = "authors"."id"', $query);
    }

    /** @test */
    public function test_join_relationship_using_alias_helper_uses_related_connection()
    {
        $query = Author::query()->joinRelationshipUsingAlias('articles')->toSql();

        $this->assertQueryContains('"secondary_db"."articles" as', $query);
    }

    /** @test */
    public function test_join_relationship_using_provided_alias_as_string()
    {
        $query = Author::query()->joinRelationship('articles', 'my_alias')->toSql();

        $this->assertQueryContains('"secondary_db"."articles" as "my_alias"', $query);
        $this->assertQueryContains('"my_alias"."author_id" = "authors"."id"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Auto-included select and column references
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_auto_select_statement_uses_base_model_connection_prefix()
    {
        $query = Author::query()->joinRelationship('articles')->toSql();

        $this->assertQueryContains('select "authors".* from "authors"', $query);
    }

    /** @test */
    public function test_auto_select_from_secondary_connection_base_uses_its_prefix()
    {
        $query = Article::query()->joinRelationship('author')->toSql();

        $this->assertQueryContains('select "articles".* from "articles"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | powerJoinHas / powerJoinWhereHas / powerJoinDoesntHave
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_power_join_has_across_connections()
    {
        $query = Author::query()->powerJoinHas('articles')->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
        $this->assertQueryNotContains('join "articles"', $query);
    }

    /** @test */
    public function test_power_join_where_has_across_connections()
    {
        $query = Author::query()->powerJoinWhereHas('articles', function ($join) {
            $join->where('articles.published', true);
        })->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
    }

    /** @test */
    public function test_scope_using_power_join_where_has_works_across_connections()
    {
        // scopeHasPublishedArticles calls powerJoinWhereHas internally.
        $query = Author::query()->hasPublishedArticles()->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
        $this->assertQueryContains('"secondary_db"."articles"."published"', $query);
    }

    /** @test */
    public function test_power_join_has_with_minimum_count_threshold_across_connections()
    {
        $query = Author::query()->powerJoinHas('articles', '>=', 2)->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
        $this->assertQueryNotContains('join "articles"', $query);
    }

    /** @test */
    public function test_power_join_doesnt_have_across_connections()
    {
        $query = Author::query()->powerJoinDoesntHave('articles')->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
    }

    /** @test */
    public function test_power_join_where_has_nested_across_connections()
    {
        $query = Author::query()->powerJoinWhereHas('articles.comments')->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
        $this->assertQueryContains('"secondary_db"."comments"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | orderBy via joined relationship
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_order_by_power_join_across_connections()
    {
        $query = Author::query()->orderByPowerJoins('articles.id')->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
        $this->assertQueryNotContains('join "articles"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Safety: the same relationship is not joined twice (regression guard)
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_does_not_join_same_cross_connection_relationship_twice()
    {
        $query = Author::query()
            ->joinRelationship('articles')
            ->joinRelationship('articles')
            ->toSql();

        $this->assertQueryContains(
            'inner join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query,
            times: 1
        );
    }

    /*
    |--------------------------------------------------------------------------
    | orderByPowerJoins aggregations
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_order_by_power_joins_count_across_connections()
    {
        $query = Author::query()->orderByPowerJoinsCount('articles.id')->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
    }

    /** @test */
    public function test_order_by_power_joins_sum_across_connections()
    {
        $query = Author::query()->orderByPowerJoinsSum('articles.id')->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
    }

    /** @test */
    public function test_order_by_power_joins_avg_across_connections()
    {
        $query = Author::query()->orderByPowerJoinsAvg('articles.id')->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
    }

    /** @test */
    public function test_order_by_power_joins_min_across_connections()
    {
        $query = Author::query()->orderByPowerJoinsMin('articles.id')->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
    }

    /** @test */
    public function test_order_by_power_joins_max_across_connections()
    {
        $query = Author::query()->orderByPowerJoinsMax('articles.id')->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
    }

    /** @test */
    public function test_order_by_left_power_joins_across_connections()
    {
        $query = Author::query()->orderByLeftPowerJoins('articles.id')->toSql();

        $this->assertQueryContains('left join "secondary_db"."articles"', $query);
    }

    /** @test */
    public function test_order_by_power_joins_three_level_nested_across_connections()
    {
        // Author (primary) -> articles (secondary_db) -> comments (secondary_db) -> id.
        // Cross-connection is measured against the base (Author/primary), so BOTH
        // articles and comments are cross-connection — both get the secondary_db qualifier.
        $query = Author::query()->orderByPowerJoins('articles.comments.id')->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
        $this->assertQueryContains('"secondary_db"."comments"', $query);
        $this->assertQueryContains(
            'inner join "secondary_db"."comments" on "secondary_db"."comments"."article_id" = "secondary_db"."articles"."id"',
            $query
        );
    }

    /*
    |--------------------------------------------------------------------------
    | joinNestedRelationship direct API
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_join_nested_relationship_direct_api_across_connections()
    {
        $query = Author::query()->joinNestedRelationship('articles.comments', joinType: 'powerJoin')->toSql();

        $this->assertQueryContains(
            'inner join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "secondary_db"."comments" on "secondary_db"."comments"."article_id" = "secondary_db"."articles"."id"',
            $query
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MorphTo cross-connection
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_morph_to_uses_target_model_connection_prefix()
    {
        // Sticker (secondary_db) -> Author (primary_db): primary is in qualifyWithDatabase,
        // so authors is qualified with primary_db.
        $query = Sticker::query()
            ->joinRelationship('stickerable', morphable: Author::class)
            ->toSql();

        $this->assertQueryContains(
            'inner join "primary_db"."authors" on "stickers"."stickerable_id" = "primary_db"."authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"secondary_db"."authors"', $query);
    }

    /** @test */
    public function test_morph_to_same_connection_morphable_does_not_add_qualifier()
    {
        // Sticker (secondary_db) -> Article (secondary_db): same connection — no DB qualifier.
        $query = Sticker::query()
            ->joinRelationship('stickerable', morphable: Article::class)
            ->toSql();

        $this->assertQueryContains(
            'inner join "articles" on "stickers"."stickerable_id" = "articles"."id"',
            $query
        );
        $this->assertQueryNotContains('"secondary_db"."articles"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | onlyTrashed on cross-connection join side
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_only_trashed_cross_connection_uses_related_prefix()
    {
        $query = Author::query()->joinRelationship('articlesOnlyTrashed')->toSql();

        $this->assertQueryContains(
            'inner join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
        $this->assertQueryContains('"secondary_db"."articles"."deleted_at" is not null', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Mixed same-connection and cross-connection joins
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_mixed_same_and_cross_connection_joins_in_one_query()
    {
        $query = Article::query()
            ->joinRelationship('author')
            ->joinRelationship('comments')
            ->toSql();

        $this->assertQueryContains('from "articles"', $query);
        $this->assertQueryContains(
            'inner join "primary_db"."authors" on "articles"."author_id" = "primary_db"."authors"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "comments" on "comments"."article_id" = "articles"."id"',
            $query
        );
        $this->assertQueryNotContains('"secondary_db"."comments"', $query);
        $this->assertQueryNotContains('"secondary_db"."authors"', $query);
    }

    /** @test */
    public function test_multiple_cross_connection_siblings_in_one_query()
    {
        $query = Author::query()
            ->joinRelationship('articles')
            ->joinRelationship('tags')
            ->joinRelationship('stickers')
            ->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
        // Pivot uses parent's connection (same as base query, no DB qualifier)
        $this->assertQueryContains('"author_tag"', $query);
        $this->assertQueryNotContains('"secondary_db"."author_tag"', $query);
        $this->assertQueryContains('"secondary_db"."tags"', $query);
        $this->assertQueryContains('"secondary_db"."stickers"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | powerJoinDoesntHave nested and powerJoinHas with callback
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_power_join_doesnt_have_nested_across_connections()
    {
        $query = Author::query()->powerJoinDoesntHave('articles.comments')->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
        $this->assertQueryContains('"secondary_db"."comments"', $query);
    }

    /** @test */
    public function test_power_join_doesnt_have_morph_many_across_connections()
    {
        // MorphMany doesntHave: the morph type discriminator in the ON clause must
        // also carry the database qualifier, not just the foreign key column.
        $query = Author::query()->powerJoinDoesntHave('stickers')->toSql();

        $this->assertQueryContains('"secondary_db"."stickers"', $query);
        $this->assertQueryContains('"secondary_db"."stickers"."stickerable_type"', $query);
    }

    /** @test */
    public function test_power_join_has_with_callback_uses_correct_prefix()
    {
        $query = Author::query()->powerJoinHas('articles', '>=', 1, 'and', function ($join) {
            $join->where('articles.published', true);
        })->toSql();

        $this->assertQueryContains('"secondary_db"."articles"', $query);
        $this->assertQueryContains('"secondary_db"."articles"."published"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Alias cache isolation between queries
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_alias_cache_does_not_bleed_between_queries()
    {
        Author::query()->joinRelationship('articles', fn ($join) => $join->as('a'))->toSql();

        $query = Author::query()->joinRelationship('articles')->toSql();

        $this->assertQueryContains(
            'inner join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
        $this->assertQueryNotContains(' as "a"', $query);
        $this->assertQueryNotContains('"a"."author_id"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Extra conditions combined with alias
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_extra_conditions_with_alias_cross_connection()
    {
        $query = Author::query()
            ->joinRelationship('publishedArticles', fn ($join) => $join->as('p'))
            ->toSql();

        $this->assertQueryContains('"secondary_db"."articles" as "p"', $query);
        $this->assertQueryContains('"p"."author_id" = "authors"."id"', $query);
        $this->assertQueryContains('"p"."published"', $query);
        $this->assertQueryNotContains('"secondary_db"."articles"."published"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | joinRelationshipUsingAlias — nested
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_join_relationship_using_alias_nested_across_connections()
    {
        $query = Author::query()->joinRelationshipUsingAlias('articles.comments')->toSql();

        $this->assertQueryContains('"secondary_db"."articles" as', $query);
        $this->assertQueryContains('"secondary_db"."comments" as', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Array alias form
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_array_alias_form_on_cross_connection_relationship()
    {
        $query = Author::query()
            ->joinRelationship('articles', [
                'articles' => fn ($join) => $join->as('art'),
            ])
            ->toSql();

        $this->assertQueryContains('"secondary_db"."articles" as "art"', $query);
        $this->assertQueryContains('"art"."author_id" = "authors"."id"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Table prefix combined with database qualifier
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_connection_prefix_is_included_in_database_qualified_table_reference()
    {
        config(['database.connections.primary' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => 'pri_',
            'foreign_key_constraints' => false,
        ]]);
        config(['database.connections.secondary' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => 'sec_',
            'foreign_key_constraints' => false,
        ]]);

        $query = Author::query()->joinRelationship('articles')->toSql();

        // On sqlite databases, the lib skips the DB qualifier and applies only the prefix if exists.
        $this->assertQueryContains(
            'inner join "sec_articles" on "sec_articles"."author_id" = "pri_authors"."id"',
            $query
        );

        $this->assertQueryNotContains('join "articles" on', $query);
        $this->assertQueryNotContains('":memory:"', $query);

        $this->getEnvironmentSetUp(app());
    }

    /*
    |--------------------------------------------------------------------------
    | Regression: same-connection join must not be qualified
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_same_connection_join_under_cross_connection_suite_still_works()
    {
        // Article (secondary_db) -> comments (secondary_db) — same connection.
        $query = Article::query()->joinRelationship('comments')->toSql();

        $this->assertQueryContains('from "articles"', $query);
        $this->assertQueryContains(
            'inner join "comments" on "comments"."article_id" = "articles"."id"',
            $query
        );
        $this->assertQueryNotContains('"secondary_db"."comments"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes — withTrashed/onlyTrashed via join callback
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_with_trashed_via_callback_on_cross_connection_belongs_to()
    {
        // Article (secondary) belongsTo Author (primary, SoftDeletes).
        // The join adds a whereNull for deleted_at as an Expression.
        // withTrashed() on the callback must remove it without crashing.
        $query = Article::query()
            ->leftJoinRelationship('author', fn ($join) => $join->withTrashed())
            ->toSql();

        $this->assertQueryContains(
            'left join "primary_db"."authors" on "articles"."author_id" = "primary_db"."authors"."id"',
            $query
        );
        // The joined model's deleted_at should be removed; base model's own WHERE deleted_at stays
        $this->assertQueryNotContains('"primary_db"."authors"."deleted_at"', $query);
    }

    /** @test */
    public function test_with_trashed_via_callback_on_cross_connection_has_many()
    {
        // Author (primary) hasMany Article (secondary, SoftDeletes).
        $query = Author::query()
            ->leftJoinRelationship('articles', fn ($join) => $join->withTrashed())
            ->toSql();

        $this->assertQueryContains(
            'left join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
        // The joined model's deleted_at should be removed; base model's own WHERE deleted_at stays
        $this->assertQueryNotContains('"secondary_db"."articles"."deleted_at"', $query);
    }

    /** @test */
    public function test_only_trashed_via_callback_on_cross_connection_belongs_to()
    {
        // Article (secondary) belongsTo Author (primary, SoftDeletes).
        // onlyTrashed() must flip whereNull to whereNotNull without crashing.
        $query = Article::query()
            ->joinRelationship('author', fn ($join) => $join->onlyTrashed())
            ->toSql();

        $this->assertQueryContains(
            'inner join "primary_db"."authors" on "articles"."author_id" = "primary_db"."authors"."id"',
            $query
        );
        $this->assertQueryContains('deleted_at', $query);
        $this->assertQueryContains('is not null', $query);
    }

    /** @test */
    public function test_only_trashed_via_callback_on_cross_connection_has_many()
    {
        $query = Author::query()
            ->joinRelationship('articles', fn ($join) => $join->onlyTrashed())
            ->toSql();

        $this->assertQueryContains(
            'inner join "secondary_db"."articles" on "secondary_db"."articles"."author_id" = "authors"."id"',
            $query
        );
        $this->assertQueryContains('deleted_at', $query);
        $this->assertQueryContains('is not null', $query);
    }

    /** @test */
    public function test_with_trashed_via_callback_on_nested_cross_connection()
    {
        // Comment (secondary) -> Article (secondary) -> Author (primary, SoftDeletes)
        $query = Comment::query()
            ->leftJoinRelationship('article.author', [
                'author' => fn ($join) => $join->withTrashed(),
            ])
            ->toSql();

        $this->assertQueryContains(
            'left join "articles" on "comments"."article_id" = "articles"."id"',
            $query
        );
        $this->assertQueryContains(
            'left join "primary_db"."authors" on "articles"."author_id" = "primary_db"."authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"authors"."deleted_at"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Pivot table — qualification uses parent model's connection
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_belongs_to_many_pivot_uses_parent_connection_for_qualification()
    {
        // Author (primary) belongsToMany Tag (secondary) via author_tag.
        // Pivot author_tag lives on primary (parent's DB), not secondary.
        $query = Author::query()->joinRelationship('tags')->toSql();

        // Pivot should use parent's DB (primary_db), not related's DB (secondary_db)
        $this->assertQueryContains(
            'inner join "author_tag" on "author_tag"."author_id" = "authors"."id"',
            $query
        );
        // Related table should still use related's DB
        $this->assertQueryContains('"secondary_db"."tags"', $query);
    }

    /** @test */
    public function test_morph_to_many_pivot_uses_parent_connection_for_qualification()
    {
        // Author (primary) morphToMany Label (secondary) via labelables.
        // Pivot should use parent's DB.
        $query = Author::query()->joinRelationship('labels')->toSql();

        // Pivot should not carry the related model's DB qualifier
        $this->assertQueryContains('"labelables"', $query);
        $this->assertQueryNotContains('"secondary_db"."labelables"', $query);
        // Related table should still use related's DB
        $this->assertQueryContains('"secondary_db"."labels"', $query);
    }

    /** @test */
    public function test_power_join_where_has_on_belongs_to_many_cross_connection()
    {
        $query = Author::query()->powerJoinWhereHas('tags', function ($join) {
            $join->where('tags.name', 'laravel');
        })->toSql();

        $this->assertQueryContains('"author_tag"', $query);
        $this->assertQueryNotContains('"secondary_db"."author_tag"', $query);
        $this->assertQueryContains('"secondary_db"."tags"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Aliases — combined with cross-connection soft deletes
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_alias_on_cross_connection_soft_delete_model()
    {
        // When combining alias + cross-DB + soft-delete, whereNull receives
        // an Expression. The alias replacement must handle it.
        $query = Article::query()
            ->joinRelationship('author', fn ($join) => $join->as('author_alias'))
            ->toSql();

        $this->assertQueryContains('"primary_db"."authors" as "author_alias"', $query);
        $this->assertQueryContains('"author_alias"."id"', $query);
        // The deleted_at clause should use the alias, not the original table
        $this->assertQueryContains('"author_alias"."deleted_at" is null', $query);
        $this->assertQueryNotContains('"primary_db"."authors"."deleted_at"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | whereNull with Expression columns and alias
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_where_null_replaces_alias_in_expression_column_cross_db()
    {
        // When disableExtraConditions removes the auto soft-delete clause, and
        // the user re-adds it manually via whereNull with a cross-db Expression
        // column, the alias replacement in whereNull must handle Expression columns.
        $authorModel = new Author();
        $query = Article::query()
            ->joinRelationship('author', function ($join) use ($authorModel) {
                $join->as('a');
                $join->whereNull(
                    \Kirschbaum\PowerJoins\ConnectionAwareTable::columnReference(
                        $authorModel,
                        Article::query(),
                        $authorModel->getDeletedAtColumn(),
                    )
                );
            }, disableExtraConditions: true)
            ->toSql();

        // The whereNull should use the alias 'a', not the original table reference
        $this->assertQueryContains('"a"."deleted_at" is null', $query);
        $this->assertQueryNotContains('"primary_db"."authors"."deleted_at"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | onlyTrashed fallback with cross-database qualification
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_only_trashed_fallback_qualifies_column_for_cross_database()
    {
        // When disableExtraConditions: true removes the auto soft-delete clause,
        // calling onlyTrashed() falls back to getQualifiedDeletedAtColumn() which
        // returns "authors.deleted_at" without the database qualifier needed for
        // cross-database joins.
        $query = Author::query()
            ->joinRelationship('articles', function ($join) {
                $join->onlyTrashed();
            }, disableExtraConditions: true)
            ->toSql();

        // The fallback must produce the fully-qualified cross-db reference
        $this->assertQueryContains('"secondary_db"."articles"."deleted_at" is not null', $query);
        $this->assertQueryNotContains(' "articles"."deleted_at" is not null', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | orderByPowerJoins aggregation with qualified table in selectRaw
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_order_by_power_joins_count_uses_qualified_table_with_prefix()
    {
        // When the secondary connection has a table prefix, orderByPowerJoinsCount
        // uses a raw selectRaw with the plain table name. The selectRaw must use the
        // same qualified reference as the join.
        config(['database.connections.secondary' => [
            'driver' => 'mysql',
            'database' => 'secondary_db',
            'prefix' => 'sec_',
            'foreign_key_constraints' => false,
        ]]);

        $query = Author::query()->orderByPowerJoinsCount('articles.id')->toSql();

        // The selectRaw must reference the prefixed, DB-qualified table
        $this->assertQueryContains('"secondary_db"."sec_articles"."id"', $query);
        // Must NOT use the raw unqualified table name in the aggregation
        $this->assertQueryNotContains('(articles.id)', $query);

        // Restore config
        $this->getEnvironmentSetUp(app());
    }

    /*
    |--------------------------------------------------------------------------
    | Pivot table with dot-qualified name
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_pivot_table_already_qualified_with_dot_is_not_re_qualified()
    {
        // When the user manually qualifies the pivot table name with a database
        // prefix (e.g., "secondary_db.author_tag"), the lib should detect the dot
        // and treat it as already qualified, not apply its own qualification on top.
        $query = Author::query()->joinRelationship('tagsOnSecondaryPivot')->toSql();

        // The pivot join should use the user-provided qualified name
        $this->assertQueryContains('"secondary_db"."author_tag"', $query);
        // The related table should still use related's connection
        $this->assertQueryContains('"secondary_db"."tags"', $query);
    }

    /** @test */
    public function test_pivot_table_already_qualified_with_prefix_is_not_double_prefixed()
    {
        // When secondary connection has a table prefix and the pivot is manually
        // qualified with the database name, the prefix should NOT be applied on
        // top of the already-qualified name.
        config(['database.connections.secondary' => [
            'driver' => 'mysql',
            'database' => 'secondary_db',
            'prefix' => 'sec_',
            'foreign_key_constraints' => false,
        ]]);

        $query = Author::query()->joinRelationship('tagsOnSecondaryPivot')->toSql();

        // The pivot should keep the user-qualified name; no extra prefix
        $this->assertQueryContains('"secondary_db"."author_tag"', $query);
        // Must NOT apply the primary connection prefix to the pivot
        $this->assertQueryNotContains('"secondary_db"."pri_author_tag"', $query);
        $this->assertQueryNotContains('"secondary_db"."sec_author_tag"', $query);

        // Restore config
        $this->getEnvironmentSetUp(app());
    }
}
