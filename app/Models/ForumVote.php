<?php

namespace App\Models;

use Database\Factories\ForumVoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'votable_type', 'votable_id'])]
class ForumVote extends Model
{
    /** @use HasFactory<ForumVoteFactory> */
    use HasFactory;

    /**
     * @return MorphTo<Model, $this>
     */
    public function votable(): MorphTo
    {
        return $this->morphTo();
    }
}
