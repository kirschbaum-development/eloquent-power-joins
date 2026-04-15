<?php

namespace Kirschbaum\PowerJoins\Tests;

use Kirschbaum\PowerJoins\ConnectionAwareTable;
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

        $app['config']->set('database.connections.testing.prefix', 'main_');

        $app['config']->set('database.connections.secondary', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => 'sec_',
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

        $this->assertQueryContains('from "main_authors"', $query);
        $this->assertQueryContains(
            'inner join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_left_join_has_many_uses_related_model_connection_prefix()
    {
        $query = Author::query()->leftJoinRelationship('articles')->toSql();

        $this->assertQueryContains(
            'left join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_right_join_has_many_uses_related_model_connection_prefix()
    {
        $query = Author::query()->rightJoinRelationship('articles')->toSql();

        $this->assertQueryContains(
            'right join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_belongs_to_uses_related_model_connection_prefix()
    {
        $query = Article::query()->joinRelationship('author')->toSql();

        $this->assertQueryContains('from "sec_articles"', $query);
        $this->assertQueryContains(
            'inner join "main_authors" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"sec_authors"', $query);
    }

    /** @test */
    public function test_has_one_uses_related_model_connection_prefix()
    {
        $query = Author::query()->joinRelationship('profile')->toSql();

        $this->assertQueryContains(
            'inner join "sec_profiles" on "sec_profiles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"main_profiles"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Nested relationships (cross-connection at multiple levels)
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_nested_has_many_across_connections()
    {
        // Author (main_) -> articles (sec_) -> comments (sec_)
        $query = Author::query()->joinRelationship('articles.comments')->toSql();

        $this->assertQueryContains(
            'inner join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "sec_comments" on "sec_comments"."article_id" = "sec_articles"."id"',
            $query
        );
        $this->assertQueryNotContains('"main_articles"', $query);
        $this->assertQueryNotContains('"main_comments"', $query);
    }

    /** @test */
    public function test_nested_belongs_to_across_connections()
    {
        // Comment (sec_) -> article (sec_) -> author (main_)
        $query = Comment::query()->joinRelationship('article.author')->toSql();

        $this->assertQueryContains(
            'inner join "sec_articles" on "sec_comments"."article_id" = "sec_articles"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "main_authors" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"sec_authors"', $query);
    }

    /** @test */
    public function test_nested_zig_zag_across_connections()
    {
        // Profile (sec_) -> author (main_) -> articles (sec_)
        $query = Profile::query()->joinRelationship('author.articles')->toSql();

        $this->assertQueryContains(
            'inner join "main_authors" on "sec_profiles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"sec_authors"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | BelongsToMany / MorphMany / MorphToMany
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_belongs_to_many_uses_related_connection_for_pivot_and_related_table()
    {
        $query = Author::query()->joinRelationship('tags')->toSql();

        $this->assertQueryContains(
            'inner join "sec_author_tag" on "sec_author_tag"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "sec_tags" on "sec_tags"."id" = "sec_author_tag"."tag_id"',
            $query
        );
        $this->assertQueryNotContains('"main_author_tag"', $query);
        $this->assertQueryNotContains('"main_tags"', $query);
    }

    /** @test */
    public function test_morph_many_uses_related_model_connection_prefix()
    {
        $query = Author::query()->joinRelationship('stickers')->toSql();

        $this->assertQueryContains(
            'inner join "sec_stickers" on "sec_stickers"."stickerable_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"main_stickers"', $query);
    }

    /** @test */
    public function test_morph_to_many_uses_related_connection_for_pivot_and_related_table()
    {
        $query = Author::query()->joinRelationship('labels')->toSql();

        $this->assertQueryContains('"sec_labelables"', $query);
        $this->assertQueryContains('"sec_labels"', $query);
        $this->assertQueryNotContains('"main_labelables"', $query);
        $this->assertQueryNotContains('"main_labels"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | HasManyThrough
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_has_many_through_uses_related_connection_for_through_and_far_tables()
    {
        // Author (main_) hasManyThrough Comment (sec_) via Article (sec_)
        $query = Author::query()->joinRelationship('commentsThroughArticles')->toSql();

        $this->assertQueryContains(
            'inner join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "sec_comments" on "sec_comments"."article_id" = "sec_articles"."id"',
            $query
        );
        $this->assertQueryNotContains('"main_articles"', $query);
        $this->assertQueryNotContains('"main_comments"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes — deleted_at clauses must also use the correct prefix
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_soft_deletes_deleted_at_clause_uses_related_connection_prefix()
    {
        $query = Author::query()->joinRelationship('articles')->toSql();

        $this->assertQueryContains('"sec_articles"."deleted_at" is null', $query);
        $this->assertQueryNotContains('"main_articles"."deleted_at"', $query);
    }

    /** @test */
    public function test_soft_deletes_base_model_uses_its_own_connection_prefix()
    {
        $query = Article::query()->joinRelationship('author')->toSql();

        // Article is soft-deletable; base table deleted_at filter applies main "from" side (sec_articles)
        // Author is soft-deletable; join-side deleted_at must use main_ prefix
        $this->assertQueryContains('"main_authors"."deleted_at" is null', $query);
        $this->assertQueryNotContains('"sec_authors"."deleted_at"', $query);
    }

    /** @test */
    public function test_with_trashed_relationship_does_not_add_deleted_at_clause()
    {
        $query = Author::query()->joinRelationship('articlesWithTrashed')->toSql();

        $this->assertQueryContains(
            'inner join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"sec_articles"."deleted_at"', $query);
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
            'inner join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryContains('"sec_articles"."published" = ?', $query);
        $this->assertQueryNotContains('"main_articles"."published"', $query);
    }

    /** @test */
    public function test_callback_closure_on_join_uses_correct_prefix()
    {
        $query = Author::query()->joinRelationship('articles', function ($join) {
            $join->where('articles.published', true);
        })->toSql();

        $this->assertQueryContains(
            'inner join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryContains('"sec_articles"."published"', $query);
        $this->assertQueryNotContains('"main_articles"."published"', $query);
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

        $this->assertQueryContains('"sec_articles" as "a"', $query);
        $this->assertQueryContains('"a"."author_id" = "main_authors"."id"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_join_relationship_using_alias_helper_uses_related_connection()
    {
        $query = Author::query()->joinRelationshipUsingAlias('articles')->toSql();

        // The real table is sec_articles; alias is auto-generated
        $this->assertQueryContains('"sec_articles" as', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_join_relationship_using_provided_alias_as_string()
    {
        $query = Author::query()->joinRelationship('articles', 'my_alias')->toSql();

        $this->assertQueryContains('"sec_articles" as "my_alias"', $query);
        $this->assertQueryContains('"my_alias"."author_id" = "main_authors"."id"', $query);
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

        $this->assertQueryContains('select "main_authors".* from "main_authors"', $query);
    }

    /** @test */
    public function test_auto_select_from_secondary_connection_base_uses_its_prefix()
    {
        $query = Article::query()->joinRelationship('author')->toSql();

        $this->assertQueryContains('select "sec_articles".* from "sec_articles"', $query);
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

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_power_join_where_has_across_connections()
    {
        $query = Author::query()->powerJoinWhereHas('articles', function ($join) {
            $join->where('articles.published', true);
        })->toSql();

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_power_join_doesnt_have_across_connections()
    {
        $query = Author::query()->powerJoinDoesntHave('articles')->toSql();

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_power_join_where_has_nested_across_connections()
    {
        $query = Author::query()->powerJoinWhereHas('articles.comments')->toSql();

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryContains('"sec_comments"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
        $this->assertQueryNotContains('"main_comments"', $query);
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

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
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
            'inner join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
            $query,
            times: 1
        );
    }

    /*
    |--------------------------------------------------------------------------
    | qualifyWithDatabaseName opt-in
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_qualify_with_database_name_prefixes_join_with_database()
    {
        ConnectionAwareTable::qualifyWithDatabaseName('secondary');

        try {
            $query = Author::query()->joinRelationship('articles')->toSql();

            // sqlite :memory: returns ":memory:" as the database name
            $this->assertQueryContains(':memory:"."sec_articles', $query);
            $this->assertQueryContains(':memory:"."sec_articles"."author_id"', $query);
        } finally {
            ConnectionAwareTable::disableDatabaseQualification('secondary');
        }
    }

    /** @test */
    public function test_disable_database_qualification_removes_database_prefix()
    {
        ConnectionAwareTable::qualifyWithDatabaseName('secondary');
        ConnectionAwareTable::disableDatabaseQualification('secondary');

        $query = Author::query()->joinRelationship('articles')->toSql();

        $this->assertQueryNotContains(':memory:"."sec_articles', $query);
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

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_order_by_power_joins_sum_across_connections()
    {
        $query = Author::query()->orderByPowerJoinsSum('articles.id')->toSql();

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_order_by_power_joins_avg_across_connections()
    {
        $query = Author::query()->orderByPowerJoinsAvg('articles.id')->toSql();

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_order_by_power_joins_min_across_connections()
    {
        $query = Author::query()->orderByPowerJoinsMin('articles.id')->toSql();

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_order_by_power_joins_max_across_connections()
    {
        $query = Author::query()->orderByPowerJoinsMax('articles.id')->toSql();

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /** @test */
    public function test_order_by_left_power_joins_across_connections()
    {
        $query = Author::query()->orderByLeftPowerJoins('articles.id')->toSql();

        $this->assertQueryContains('left join "sec_articles"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
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
            'inner join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "sec_comments" on "sec_comments"."article_id" = "sec_articles"."id"',
            $query
        );
        $this->assertQueryNotContains('"main_articles"', $query);
        $this->assertQueryNotContains('"main_comments"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | MorphTo cross-connection
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_morph_to_uses_target_model_connection_prefix()
    {
        $query = Sticker::query()
            ->joinRelationship('stickerable', morphable: Author::class)
            ->toSql();

        $this->assertQueryContains(
            'inner join "main_authors" on "sec_stickers"."stickerable_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryNotContains('"sec_authors"', $query);
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
            'inner join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryContains('"sec_articles"."deleted_at" is not null', $query);
        $this->assertQueryNotContains('"main_articles"."deleted_at"', $query);
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

        $this->assertQueryContains('from "sec_articles"', $query);
        $this->assertQueryContains(
            'inner join "main_authors" on "sec_articles"."author_id" = "main_authors"."id"',
            $query
        );
        $this->assertQueryContains(
            'inner join "sec_comments" on "sec_comments"."article_id" = "sec_articles"."id"',
            $query
        );
        $this->assertQueryNotContains('"main_comments"', $query);
        $this->assertQueryNotContains('"sec_authors"', $query);
    }

    /** @test */
    public function test_multiple_cross_connection_siblings_in_one_query()
    {
        $query = Author::query()
            ->joinRelationship('articles')
            ->joinRelationship('tags')
            ->joinRelationship('stickers')
            ->toSql();

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryContains('"sec_author_tag"', $query);
        $this->assertQueryContains('"sec_tags"', $query);
        $this->assertQueryContains('"sec_stickers"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
        $this->assertQueryNotContains('"main_tags"', $query);
        $this->assertQueryNotContains('"main_stickers"', $query);
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

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryContains('"sec_comments"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
        $this->assertQueryNotContains('"main_comments"', $query);
    }

    /** @test */
    public function test_power_join_has_with_callback_uses_correct_prefix()
    {
        $query = Author::query()->powerJoinHas('articles', '>=', 1, 'and', function ($join) {
            $join->where('articles.published', true);
        })->toSql();

        $this->assertQueryContains('"sec_articles"', $query);
        $this->assertQueryContains('"sec_articles"."published"', $query);
        $this->assertQueryNotContains('"main_articles"."published"', $query);
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
            'inner join "sec_articles" on "sec_articles"."author_id" = "main_authors"."id"',
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

        $this->assertQueryContains('"sec_articles" as "p"', $query);
        $this->assertQueryContains('"p"."author_id" = "main_authors"."id"', $query);
        $this->assertQueryContains('"p"."published"', $query);
        $this->assertQueryNotContains('"main_articles"."published"', $query);
        $this->assertQueryNotContains('"sec_articles"."published"', $query);
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

        $this->assertQueryContains('"sec_articles" as', $query);
        $this->assertQueryContains('"sec_comments" as', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
        $this->assertQueryNotContains('"main_comments"', $query);
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

        $this->assertQueryContains('"sec_articles" as "art"', $query);
        $this->assertQueryContains('"art"."author_id" = "main_authors"."id"', $query);
        $this->assertQueryNotContains('"main_articles"', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Regression: same-connection join must not wrap table in Expression
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function test_same_connection_join_under_cross_connection_suite_still_works()
    {
        // Article (sec_) -> comments (sec_) — both on the same connection.
        $query = Article::query()->joinRelationship('comments')->toSql();

        $this->assertQueryContains('from "sec_articles"', $query);
        $this->assertQueryContains(
            'inner join "sec_comments" on "sec_comments"."article_id" = "sec_articles"."id"',
            $query
        );
        $this->assertQueryNotContains('"main_', $query);
    }
}
