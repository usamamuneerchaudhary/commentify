<?php

use Illuminate\Database\Eloquent\Model;
use Usamamuneerchaudhary\Commentify\Scopes\CommentScopes;

class EpisodeStub extends Model
{
    use \Usamamuneerchaudhary\Commentify\Traits\Commentable;

    protected $connection = 'testbench';

    public $table = 'episodes';

}
