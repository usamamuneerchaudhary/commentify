<?php

use Illuminate\Support\HtmlString;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Models\Presenters\CommentPresenter;
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
            'slug' => \Illuminate\Support\Str::slug('Article One'),
        ]);
        $this->user = User::factory()->create([
            'comment_banned_until' => null, // Not banned
        ]);

        $this->comment = $this->article->comments()->create([
            'body' => 'This is a test comment',
            'commentable_type' => '\ArticleStub',
            'commentable_id' => $this->article->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ]);

        $this->commentPresenter = new CommentPresenter($this->comment);
    }

    public function test_it_can_convert_comment_body_to_markdown_html(): void
    {
        $expectedOutput = 'This is a test comment';
        $this->assertEquals(new HtmlString(app('markdown')->convertToHtml($expectedOutput)),
            $this->commentPresenter->markdownBody());
    }

    public function test_it_can_get_relative_created_at_time(): void
    {
        $expectedOutput = '1 hour ago';
        $this->assertEquals($expectedOutput, $this->commentPresenter->relativeCreatedAt());
    }

    public function test_it_can_replace_user_mentions_in_text_with_links(): void
    {
        $expectedOutput = 'Hello <a href="/users/usama">@usama</a>, this is a test comment mentioning!';
        $this->assertEquals($expectedOutput, $this->commentPresenter->replaceUserMentions($expectedOutput));
    }

    public function test_preview_renders_markdown_to_html(): void
    {
        $html = CommentPresenter::preview('**bold** and *italic*');

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<em>italic</em>', $html);
    }

    public function test_preview_supports_strikethrough_and_autolink(): void
    {
        $html = CommentPresenter::preview('This is ~~gone~~ and https://example.com');

        $this->assertStringContainsString('<del>gone</del>', $html);
        $this->assertStringContainsString('href="https://example.com"', $html);
    }

    public function test_preview_supports_task_lists(): void
    {
        $html = CommentPresenter::preview("- [ ] todo\n- [x] done");

        $this->assertStringContainsString('type="checkbox"', $html);
    }

    public function test_preview_strips_unsafe_html(): void
    {
        $html = CommentPresenter::preview('Hello <script>alert(1)</script><b>world</b>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('</script>', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('world', $html);
    }
}
