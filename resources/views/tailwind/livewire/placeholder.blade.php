<div>
    <section class="bg-white py-8 lg:py-16 dark:bg-gray-900" aria-busy="true">
        <div class="mx-auto flex max-w-2xl flex-col gap-6 px-4">
            <p class="text-lg font-bold text-gray-900 lg:text-2xl dark:text-white">
                {{ __('commentify::commentify.comments.loading') }}
            </p>
            <div class="flex flex-col gap-4">
                @foreach (range(1, 3) as $row)
                    <div class="flex animate-pulse flex-col gap-3 rounded-lg bg-gray-100 p-6 dark:bg-gray-800">
                        <div class="h-4 w-40 rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="h-3 w-full rounded bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
