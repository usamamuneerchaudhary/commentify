<?php

use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Usamamuneerchaudhary\Commentify\Filament\CommentsEntry;
use Usamamuneerchaudhary\Commentify\Support\CommentThreadKey;

class CommentThreadKeyTest extends TestCase
{
    public function test_the_key_includes_the_morph_class_and_id(): void
    {
        $article = ArticleStub::create(['slug' => 'one']);
        $episode = EpisodeStub::create(['slug' => 'one']);
        $again = $article->fresh();

        $this->assertSame(
            'commentify-'.$article->getMorphClass().'-'.$article->getKey(),
            CommentThreadKey::for($article),
        );
        $this->assertSame(CommentThreadKey::for($article), CommentThreadKey::for($again));
        $this->assertNotSame(CommentThreadKey::for($article), CommentThreadKey::for($episode));
        $this->assertSame(
            CommentThreadKey::for($article).'-reviews',
            CommentThreadKey::for($article, 'reviews'),
        );
    }

    public function test_the_thread_component_keys_livewire_to_the_record(): void
    {
        $article = ArticleStub::create(['slug' => 'keyed']);
        $key = CommentThreadKey::for($article);

        $html = $this->renderThread($article);

        $this->assertStringContainsString('wire:key="'.$key.'"', $html);
        $this->assertStringContainsString('"thread":"default"', html_entity_decode($html));
    }

    public function test_the_thread_component_passes_a_custom_thread_name(): void
    {
        $article = ArticleStub::create(['slug' => 'named']);

        $html = $this->renderThread($article, 'reviews');

        $this->assertStringContainsString('wire:key="'.CommentThreadKey::for($article, 'reviews').'"', $html);
        $this->assertStringContainsString('"thread":"reviews"', html_entity_decode($html));
    }

    public function test_the_filament_view_mounts_the_open_record(): void
    {
        $article = ArticleStub::create(['slug' => 'open']);

        $html = $this->renderFilamentThread(fn () => $article);

        $this->assertStringContainsString('wire:key="'.CommentThreadKey::for($article).'"', $html);
    }

    public function test_the_filament_view_renders_nothing_without_a_saved_record(): void
    {
        $unsaved = new ArticleStub;
        $unsaved->slug = 'draft';

        $this->assertSame('', trim($this->renderFilamentThread(fn () => null)));
        $this->assertSame('', trim($this->renderFilamentThread(fn () => 'missing')));
        $this->assertSame('', trim($this->renderFilamentThread(fn () => $unsaved)));
    }

    public function test_comments_entry_uses_the_filament_thread_and_hides_while_creating(): void
    {
        if (! class_exists(View::class)) {
            require __DIR__.'/../stubs/FilamentSchemaViewStub.php';
        }

        $entry = CommentsEntry::make();

        $this->assertSame('commentify::filament.thread', $entry->getView());
        $this->assertContains('create', $entry->hiddenOperations);
    }

    protected function renderThread(Model $model, ?string $thread = null): string
    {
        $threadAttribute = filled($thread) ? ' thread="'.$thread.'"' : '';

        return Blade::render(
            '<x-commentify::thread :model="$model"'.$threadAttribute.' />',
            ['model' => $model],
        );
    }

    protected function renderFilamentThread(callable $getRecord): string
    {
        return view('commentify::filament.thread', [
            'getRecord' => $getRecord,
        ])->render();
    }
}
