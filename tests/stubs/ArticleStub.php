<?php

use Illuminate\Database\Eloquent\Model;
use Usamamuneerchaudhary\Commentify\Scopes\CommentScopes;

class ArticleStub extends Model
{
    use \Usamamuneerchaudhary\Commentify\Traits\Commentable;

    protected $connection = 'testbench';

    public $table = 'articles';

}
