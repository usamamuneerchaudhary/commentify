@php

        $theme = config('commentify.theme', 'auto');
        $initialDarkAttr = $theme === 'dark' ? 'data-bs-theme="dark"' : '';
@endphp

<div
    class="commentify-wrapper"
    {!! $initialDarkAttr !!}
    @if($theme === 'auto')
        x-data="{
            init() {
                const checkTheme = () => {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (prefersDark) {
                        this.$el.setAttribute('data-bs-theme', 'dark');
                    } else {
                        this.$el.removeAttribute('data-bs-theme');
                    }
                };
                checkTheme();
                window.matchMedia('(prefers-color-scheme: dark)')
                    .addEventListener('change', checkTheme);
            }
        }"
    @endif
>
    <section class="py-4 py-md-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-8">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h3 mb-0">{{ __('commentify::commentify.comments.discussion') }} ({{$comments->total()}})</h2>
                        @if(config('commentify.enable_sorting', true) && $comments->total() > 0)
                            <div class="dropdown position-relative" x-data="{ open: false }">
                                <button @click="open = !open" type="button" class="btn btn-outline-secondary btn-sm" :class="{ 'show': open }" :aria-expanded="open">
                                    {{ __('commentify::commentify.comments.sort_by') }}
                                    <svg class="bi ms-1" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/>
                                    </svg>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow" 
                                    x-show="open" 
                                    @click.away="open = false"
                                    x-cloak
                                    :class="{ 'show': open }"
                                    style="display: none;">
                                    <li>
                                        <button wire:click="$set('sort', 'newest')" @click="open = false" type="button" class="dropdown-item {{ $sort === 'newest' ? 'active' : '' }}">
                                            {{ __('commentify::commentify.comments.sort_newest') }}
                                        </button>
                                    </li>
                                    <li>
                                        <button wire:click="$set('sort', 'oldest')" @click="open = false" type="button" class="dropdown-item {{ $sort === 'oldest' ? 'active' : '' }}">
                                            {{ __('commentify::commentify.comments.sort_oldest') }}
                                        </button>
                                    </li>
                                    <li>
                                        <button wire:click="$set('sort', 'most_liked')" @click="open = false" type="button" class="dropdown-item {{ $sort === 'most_liked' ? 'active' : '' }}">
                                            {{ __('commentify::commentify.comments.sort_most_liked') }}
                                        </button>
                                    </li>
                                    <li>
                                        <button wire:click="$set('sort', 'most_replied')" @click="open = false" type="button" class="dropdown-item {{ $sort === 'most_replied' ? 'active' : '' }}">
                                            {{ __('commentify::commentify.comments.sort_most_replied') }}
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        @endif
                    </div>
                    @auth
                        @include('commentify::livewire.partials.comment-form',[
                            'method'=>'postComment',
                            'state'=>'newCommentState',
                            'inputId'=> 'comment',
                            'inputLabel'=> __('commentify::commentify.comments.your_comment'),
                            'button'=> __('commentify::commentify.comments.post_comment')
                        ])
                    @else
                        <p class="text-muted">
                            <a href="{{ route('login', ['redirect' => request()->url()]) }}" class="text-decoration-none">
                                {{ __('commentify::commentify.comments.login_to_comment') }}
                            </a>
                        </p>
                    @endauth
                    @if($comments->count())
                        @foreach($comments as $comment)
                            <livewire:comment :$comment :key="$comment->id"/>
                        @endforeach
                        <div class="mt-4">
                            {{$comments->links()}}
                        </div>
                    @else
                        <p class="text-muted">{{ __('commentify::commentify.comments.no_comments') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>

