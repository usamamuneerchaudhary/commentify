@if (!config('commentify.read_only'))
    <form class="mb-6" wire:submit="{{$method}}">
        @if (session()->has('message'))
            @php
                $alertType = session('alertType', 'success');
                $alertClasses = [
                    'success' => 'text-green-800 bg-green-50 dark:text-green-400',
                    'warning' => 'text-yellow-800 bg-yellow-50 dark:text-yellow-400',
                    'error'   => 'text-red-800 bg-red-50 dark:text-red-400',
                ];
            @endphp
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
                <div class="p-4 mb-4 text-sm rounded-lg {{ $alertClasses[$alertType] ?? $alertClasses['success'] }}"
                     role="alert">
                    <span class="font-medium">{{ ucfirst($alertType) }}!</span> {{ session('message') }}
                </div>
            </div>
        @endif
        @csrf
        <div
            class="py-2 px-4 mb-4 bg-white rounded-lg rounded-t-lg border border-gray-200 dark:bg-gray-800 dark:border-gray-700
             ">
            <label for="{{$inputId}}" class="sr-only">{{$inputLabel}}</label>
            <div x-data="{
        detectAtSymbol(event) {
            const textarea = event.target;
            const cursorPosition = textarea.selectionStart;
            const textBeforeCursor = textarea.value.substring(0, cursorPosition);
            const atSymbolPosition = textBeforeCursor.lastIndexOf('@');
            if (atSymbolPosition !== -1) {
                const searchTerm = textBeforeCursor.substring(atSymbolPosition + 1);
                if (searchTerm.trim().length > 0) {
                    this.$wire.getUsers(searchTerm);
                }
            }
        },
        showEmojiPicker: false,
        emojis: ['😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈', '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽', '👾', '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾'],
        insertEmoji(emoji) {
            const textarea = document.getElementById('{{$inputId}}');
            const cursorPosition = textarea.selectionStart;
            const textBefore = textarea.value.substring(0, cursorPosition);
            const textAfter = textarea.value.substring(cursorPosition);
            const newText = textBefore + emoji + textAfter;
            @this.set('{{$state}}.body', newText);
            this.showEmojiPicker = false;
            textarea.focus();
            setTimeout(() => {
                textarea.setSelectionRange(cursorPosition + emoji.length, cursorPosition + emoji.length);
            }, 0);
        }
    }">
                <div class="flex items-start gap-2">
                    <textarea id="{{$inputId}}" rows="6"
                              class="flex-1 px-0 w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none
                                          dark:text-white dark:placeholder-gray-400 dark:bg-gray-800 @error($state.'.body')
                                          border-red-500 @enderror"
                              placeholder="{{ __('commentify::commentify.comments.write_comment') }}"
                              wire:model.live="{{$state}}.body"
                              @input="detectAtSymbol"
                    ></textarea>
                    @if(config('commentify.enable_emoji_picker', true))
                        <div class="relative">
                            <button type="button" @click="showEmojiPicker = !showEmojiPicker" class="p-2 text-gray-500 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-400" title="{{ __('commentify::commentify.comments.add_emoji') }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </button>
                            <div x-show="showEmojiPicker" @click.away="showEmojiPicker = false" x-cloak class="absolute bottom-full right-0 mb-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 dark:bg-gray-800 dark:border-gray-700 z-50">
                                <div class="p-3 max-h-64 overflow-y-auto">
                                    <div class="grid grid-cols-8 gap-1">
                                        <template x-for="emoji in emojis" :key="emoji">
                                            <button type="button" @click="insertEmoji(emoji)" class="p-1 text-2xl hover:bg-gray-100 dark:hover:bg-gray-700 rounded" x-text="emoji"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @if(!empty($users) && $users->count() > 0)
                @include('commentify::livewire.partials.dropdowns.users')
            @endif
            @error($state.'.body')
            <p class="mt-2 text-sm text-red-600">
                {{$message}}
            </p>
            @enderror
        </div>


        <flux:button
            variant="primary"
            wire:loading.attr="disabled"
            type="submit">
             <span wire:loading wire:target="{{ $method }}" class="mr-2">
            @include('commentify::livewire.partials.loader')
        </span>
            <span wire:loading.remove wire:target="{{ $method }}">
            {{ $button }}
        </span>
        </flux:button>

    </form>
@else
    <div class="text-gray-500 italic">{{ __('commentify::commentify.comments.read_only_message') }}</div>
@endif
