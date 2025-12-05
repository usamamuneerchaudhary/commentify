@if (!config('commentify.read_only'))
    <form class="mb-4" wire:submit="{{$method}}">
        @if (session()->has('message'))
            @php
                $alertType = session('alertType', 'success');
                $alertClasses = [
                    'success' => 'alert-success',
                    'warning' => 'alert-warning',
                    'error'   => 'alert-danger',
                ];
            @endphp
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
                <div class="alert {{ $alertClasses[$alertType] ?? $alertClasses['success'] }} alert-dismissible fade show" role="alert">
                    <strong>{{ ucfirst($alertType) }}!</strong> {{ session('message') }}
                    <button type="button" class="btn-close" @click="show = false" aria-label="Close"></button>
                </div>
            </div>
        @endif
        @csrf
        <div class="card mb-3">
            <div class="card-body">
                <label for="{{$inputId}}" class="form-label visually-hidden">{{$inputLabel}}</label>
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
                    initEmojiPicker() {
                        const picker = document.getElementById('emoji-picker-{{$inputId}}');
                        if (picker && !picker.hasAttribute('data-initialized')) {
                            picker.addEventListener('emoji-click', (event) => {
                                const emoji = event.detail.emoji.unicode;
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
                            });
                            picker.setAttribute('data-initialized', 'true');
                        }
                    }
                }"
                x-init="
                    if (typeof window.loadEmojiPicker === 'undefined') {
                        window.loadEmojiPicker = function() {
                            if (document.getElementById('emoji-picker-script')) return;
                            const script = document.createElement('script');
                            script.id = 'emoji-picker-script';
                            script.type = 'module';
                            script.src = 'https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js';
                            document.head.appendChild(script);
                        };
                        window.loadEmojiPicker();
                    }
                ">
                    <div class="d-flex gap-2">
                        <textarea 
                            id="{{$inputId}}" 
                            rows="4"
                            class="form-control @error($state.'.body') is-invalid @enderror"
                            placeholder="{{ __('commentify::commentify.comments.write_comment') }}"
                            wire:model.live="{{$state}}.body"
                            @input="detectAtSymbol"
                        ></textarea>
                        @if(config('commentify.enable_emoji_picker', true))
                            <div class="position-relative">
                                <button 
                                    type="button" 
                                    @click="showEmojiPicker = !showEmojiPicker; $nextTick(() => initEmojiPicker())" 
                                    class="btn btn-outline-secondary" 
                                    title="{{ __('commentify::commentify.comments.add_emoji') }}"
                                    style="height: fit-content;">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                                <div 
                                    x-show="showEmojiPicker" 
                                    @click.away="showEmojiPicker = false" 
                                    x-cloak 
                                    class="position-absolute bottom-100 end-0 mb-2"
                                    style="width: 352px; max-width: calc(100vw - 2rem); z-index: 1050;">
                                    <style>
                                        emoji-picker {
                                            --background: var(--bs-body-bg, white);
                                            --border-color: var(--bs-border-color, #dee2e6);
                                            --text-color: var(--bs-body-color, #212529);
                                            --num-columns: 8;
                                            --category-emoji-size: 1.5rem;
                                            --emoji-size: 1.75rem;
                                            border-radius: 0.375rem;
                                            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                                        }
                                        [data-bs-theme="dark"] emoji-picker {
                                            --background: var(--bs-body-bg);
                                            --border-color: var(--bs-border-color);
                                            --text-color: var(--bs-body-color);
                                        }
                                    </style>
                                    <emoji-picker id="emoji-picker-{{$inputId}}"></emoji-picker>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @if(!empty($users) && $users->count() > 0)
                    @include('commentify::livewire.partials.dropdowns.users')
                @endif
                @error($state.'.body')
                    <div class="invalid-feedback d-block">
                        {{$message}}
                    </div>
                @enderror
            </div>
        </div>
        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary">
            <span wire:loading wire:target="{{ $method }}" class="spinner-border spinner-border-sm me-2" role="status"></span>
            <span wire:loading.remove wire:target="{{ $method }}">
                {{ $button }}
            </span>
        </button>
    </form>
@else
    <div class="text-muted fst-italic">{{ __('commentify::commentify.comments.read_only_message') }}</div>
@endif

