<?php

namespace KirschbaumDevelopment\EloquentJoins\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_groups', 'group_id', 'post_id');
    }

    public function parentGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_parent', 'parent_group_id', 'group_id');
    }
}
