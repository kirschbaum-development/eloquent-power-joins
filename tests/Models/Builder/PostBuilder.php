<?php

namespace Kirschbaum\PowerJoins\Tests\Models\Builder;

use Illuminate\Database\Eloquent\Builder;

class PostBuilder extends Builder
{
    public function whereReviewed(): self
    {
        return $this->where('reviewed', true);
    }
}
