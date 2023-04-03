<div>

    <section class="bg-white dark:bg-gray-900 py-8 lg:py-16">
        <div class="max-w-2xl mx-auto px-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg lg:text-2xl font-bold text-gray-900 dark:text-white">Discussion
                    ({{$comments->count()}})</h2>
            </div>
            @auth
                <form class="mb-6" wire:submit.prevent="postComment">
                    <div
                        class="py-2 px-4 mb-4 bg-white rounded-lg rounded-t-lg border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <label for="comment" class="sr-only">Your comment</label>
                        <textarea id="comment" rows="6"
                                  class="px-0 w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none dark:text-white dark:placeholder-gray-400 dark:bg-gray-800"
                                  placeholder="Write a comment..." required
                                  wire:model.defer="newCommentState.body"
                        ></textarea>
                        @error('newCommentState.body')
                        <p class="mt-2 text-sm text-red-600">
                            {{$message}}
                        </p>
                        @enderror
                    </div>
                    <button type="submit"
                            class="inline-flex items-center py-2.5 px-4 text-xs font-medium text-center text-white bg-primary-700 rounded-lg focus:ring-4 focus:ring-primary-200 dark:focus:ring-primary-900 hover:bg-primary-800">
                        Post comment
                    </button>
                </form>
            @else
                <a class="mt-2 text-sm" href="/login">Log in to comment!</a>
            @endauth
            @if($comments->count())
                @foreach($comments as $comment)
                    <livewire:comment :comment="$comment" :key="$comment->id"/>
                @endforeach
                {{$comments->links()}}
            @else
                <p>No comments yet!</p>
            @endif
        </div>
    </section>
</div>
