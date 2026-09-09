@if ($showGuestFields ?? false)
    <div class="mb-4 grid gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $inputId }}-guest-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                {{ __('commentify::commentify.comments.guest_name') }}
            </label>
            <input
                type="text"
                id="{{ $inputId }}-guest-name"
                wire:model="guest_name"
                required
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500 @error('guest_name') border-red-500 @enderror"
                placeholder="{{ __('commentify::commentify.comments.guest_name_placeholder') }}"
            />
            @error('guest_name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="{{ $inputId }}-guest-email" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                {{ __('commentify::commentify.comments.guest_email') }}
            </label>
            <input
                type="email"
                id="{{ $inputId }}-guest-email"
                wire:model="guest_email"
                @if (config('commentify.guest.require_email', true)) required @endif
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500 @error('guest_email') border-red-500 @enderror"
                placeholder="{{ __('commentify::commentify.comments.guest_email_placeholder') }}"
            />
            @error('guest_email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>
@endif
