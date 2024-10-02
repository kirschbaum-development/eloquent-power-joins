<?php

namespace Kirschbaum\PowerJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    /** @var string */
    protected $table = 'groups';

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_groups', 'group_id', 'post_id');
    }

    /**
     * Some relationships just don't make a lot of sense, but is just for testing anyway :).
     */
    public function recentPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_groups', 'group_id', 'post_id')
            ->wherePivot('assigned_at', '>=', now()->subWeek());
    }

    public function publishedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_groups', 'group_id', 'post_id')->published();
    }

    public function parentGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_parent', 'parent_group_id', 'group_id');
    }
}
