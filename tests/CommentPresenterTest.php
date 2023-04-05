<?php

use Usamamuneerchaudhary\Commentify\Models\Presenters\CommentPresenter;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Illuminate\Support\HtmlString;
use Usamamuneerchaudhary\Commentify\Models\User;

class CommentPresenterTest extends TestCase
{
    /**
     * @var Comment
     */
    protected $comment;

    /**
     * @var CommentPresenter
     */
    protected $commentPresenter;

    public function setUp(): void
    {
        parent::setUp();

        $this->article = \ArticleStub::create([
            'slug' => \Illuminate\Support\Str::slug('Article One')
        ]);
        $this->user = User::factory()->create();

        $this->comment = $this->article->comments()->create([
            'body' => 'This is a test comment',
            'commentable_type' => '\ArticleStub',
            'commentable_id' => $this->article->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);

        $this->commentPresenter = new CommentPresenter($this->comment);
    }

    /** @test */
    public function it_can_convert_comment_body_to_markdown_html()
    {
        $expectedOutput = 'This is a test comment';
        $this->assertEquals(new HtmlString(app('markdown')->convertToHtml($expectedOutput)),
            $this->commentPresenter->markdownBody());
    }

    /** @test */
    public function it_can_get_relative_created_at_time()
    {
        $expectedOutput = '1 hour ago';
        $this->assertEquals($expectedOutput, $this->commentPresenter->relativeCreatedAt());
    }


    /** @test */
    public function it_can_replace_user_mentions_in_text_with_links()
    {
        $expectedOutput = 'Hello <a href="/users/usama">@usama</a>, this is a test comment mentioning!';
        $this->assertEquals($expectedOutput, $this->commentPresenter->replaceUserMentions($expectedOutput));
    }
}
