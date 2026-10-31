<div>
    <section class="py-4" aria-busy="true">
        <p class="h4">{{ __('commentify::commentify.comments.loading') }}</p>
        <div class="d-flex flex-column gap-3 mt-3">
            @foreach (range(1, 3) as $row)
                <div class="placeholder-glow border rounded p-3">
                    <span class="placeholder col-4"></span>
                    <span class="placeholder col-12 mt-2"></span>
                </div>
            @endforeach
        </div>
    </section>
</div>
