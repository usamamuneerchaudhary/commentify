@props([
    'model',
    'thread' => null,
])

@if (filled($thread))
    <livewire:comments
        :model="$model"
        :thread="$thread"
        :key="\Usamamuneerchaudhary\Commentify\Support\CommentThreadKey::for($model)"
    />
@else
    <livewire:comments
        :model="$model"
        :key="\Usamamuneerchaudhary\Commentify\Support\CommentThreadKey::for($model)"
    />
@endif
