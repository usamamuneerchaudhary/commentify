@php
    $theme = config('commentify.theme', 'auto');
    $initialDarkClass = $theme === 'dark' ? 'dark' : '';
@endphp

<div
    class="commentify-wrapper {{ $initialDarkClass }}"
    @if($theme === 'auto')
        x-data="{
            init() {
                // Check system preference on load
                const checkTheme = () => {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    this.$el.classList.toggle('dark', prefersDark);
                };

                // Set initial theme
                checkTheme();

                // Listen for system theme changes
                window.matchMedia('(prefers-color-scheme: dark)')
                    .addEventListener('change', checkTheme);
            }
        }"
    @endif
>
    <section class="bg-white dark:bg-gray-900 py-8 lg:py-16">
        <div class="max-w-2xl mx-auto px-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg lg:text-2xl font-bold text-gray-900 dark:text-white">{{ __('commentify::commentify.comments.discussion') }}
                    ({{$comments->total()}})</h2>
                @if(config('commentify.enable_sorting', true) && $comments->total() > 0)
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            {{ __('commentify::commentify.comments.sort_by') }}
                            <svg class="w-2.5 h-2.5 ml-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute z-10 right-0 mt-2 w-48 bg-white rounded-lg shadow-lg dark:bg-gray-700">
                            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                                <li>
                                    <button wire:click="$set('sort', 'newest')" type="button" class="block w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ $sort === 'newest' ? 'bg-gray-100 dark:bg-gray-600' : '' }}">
                                        {{ __('commentify::commentify.comments.sort_newest') }}
                                    </button>
                                </li>
                                <li>
                                    <button wire:click="$set('sort', 'oldest')" type="button" class="block w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ $sort === 'oldest' ? 'bg-gray-100 dark:bg-gray-600' : '' }}">
                                        {{ __('commentify::commentify.comments.sort_oldest') }}
                                    </button>
                                </li>
                                <li>
                                    <button wire:click="$set('sort', 'most_liked')" type="button" class="block w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ $sort === 'most_liked' ? 'bg-gray-100 dark:bg-gray-600' : '' }}">
                                        {{ __('commentify::commentify.comments.sort_most_liked') }}
                                    </button>
                                </li>
                                <li>
                                    <button wire:click="$set('sort', 'most_replied')" type="button" class="block w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ $sort === 'most_replied' ? 'bg-gray-100 dark:bg-gray-600' : '' }}">
                                        {{ __('commentify::commentify.comments.sort_most_replied') }}
                                    </button>
                                </li>
                            </ul>
                        </div>
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
                <a class="mt-2 text-sm text-gray-600 dark:text-gray-400 hover:underline" href="{{ route('login', ['redirect' => request()->url()]) }}">{{ __('commentify::commentify.comments.login_to_comment') }}</a>
            @endauth
            @if($comments->count())
                @foreach($comments as $comment)
                    <livewire:comment :$comment :key="$comment->id"/>
                @endforeach
                <div class="mt-4">
                    {{$comments->links()}}
                </div>
            @else
                <p class="text-gray-600 dark:text-gray-400">{{ __('commentify::commentify.comments.no_comments') }}</p>
            @endif
        </div>
    </section>
</div>
