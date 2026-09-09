@php
    $toolbarBtn = 'rounded px-2 py-1 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700';
@endphp
@if (!config('commentify.read_only'))
    <form class="mb-6" wire:submit="{{$method}}">
        @if (session()->has('message'))
            @php
                $alertType = session('alertType', 'success');
                $alertClasses = [
                    'success' => 'text-green-800 bg-green-50 dark:text-green-300 dark:bg-green-900/20',
                    'warning' => 'text-yellow-800 bg-yellow-50 dark:text-yellow-300 dark:bg-yellow-900/20',
                    'error'   => 'text-red-800 bg-red-50 dark:text-red-300 dark:bg-red-900/20',
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
        @include('commentify::livewire.partials.guest-fields', [
            'showGuestFields' => config('commentify.allow_guests', false) && auth()->guest(),
        ])
        <div
            class="relative mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
            @if (config('commentify-pro.media.enabled', false))
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop($event)"
            @endif
            x-data="{
                mode: 'write',
                previewHtml: '',
                previewLoading: false,
                showEmojiPicker: false,
                dragging: false,
                emptyPreview: @js(__('commentify::commentify.comments.preview_empty')),
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
                    this.autoGrow();
                },
                autoGrow() {
                    const textarea = this.$refs.textarea;
                    if (! textarea) {
                        return;
                    }
                    textarea.style.height = 'auto';
                    textarea.style.height = Math.min(textarea.scrollHeight, 320) + 'px';
                },
                getBody() {
                    return this.$wire.get('{{ $state }}.body') || '';
                },
                setBody(value, cursorStart = null, cursorEnd = null) {
                    @this.set('{{ $state }}.body', value);
                    this.$nextTick(() => {
                        const textarea = this.$refs.textarea;
                        if (! textarea) {
                            return;
                        }
                        textarea.focus();
                        if (cursorStart !== null && cursorEnd !== null) {
                            textarea.setSelectionRange(cursorStart, cursorEnd);
                        }
                        this.autoGrow();
                    });
                },
                insertAtCursor(text) {
                    const textarea = this.$refs.textarea;
                    if (! textarea) {
                        return;
                    }
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const value = textarea.value;
                    const next = value.substring(0, start) + text + value.substring(end);
                    const cursor = start + text.length;
                    this.setBody(next, cursor, cursor);
                },
                wrapSelection(before, after = '', placeholder = '') {
                    const textarea = this.$refs.textarea;
                    if (! textarea) {
                        return;
                    }
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const value = textarea.value;
                    const selected = value.substring(start, end) || placeholder;
                    const next = value.substring(0, start) + before + selected + after + value.substring(end);
                    const cursorStart = start + before.length;
                    const cursorEnd = cursorStart + selected.length;
                    this.setBody(next, cursorStart, cursorEnd);
                },
                insertLinePrefix(prefix) {
                    const textarea = this.$refs.textarea;
                    if (! textarea) {
                        return;
                    }
                    const start = textarea.selectionStart;
                    const value = textarea.value;
                    const lineStart = value.lastIndexOf('\n', start - 1) + 1;
                    const next = value.substring(0, lineStart) + prefix + value.substring(lineStart);
                    const cursor = start + prefix.length;
                    this.setBody(next, cursor, cursor);
                },
                insertCodeBlock() {
                    this.wrapSelection('```\n', '\n```', '{{ __('commentify::commentify.comments.toolbar_code_block_placeholder') }}');
                },
                insertMention() {
                    this.insertAtCursor('@');
                    const textarea = this.$refs.textarea;
                    if (textarea) {
                        this.$nextTick(() => {
                            textarea.dispatchEvent(new Event('input', { bubbles: true }));
                        });
                    }
                },
                async setMode(mode) {
                    this.mode = mode;
                    this.showEmojiPicker = false;
                    if (mode === 'preview') {
                        this.previewLoading = true;
                        try {
                            const body = this.getBody();
                            this.previewHtml = body.trim().length
                                ? await this.$wire.previewMarkdown(body)
                                : this.emptyPreview;
                        } finally {
                            this.previewLoading = false;
                        }
                    } else {
                        this.$nextTick(() => this.autoGrow());
                    }
                },
                ensureEmojiPickerLoaded() {
                    if (typeof window.loadEmojiPicker === 'undefined') {
                        window.loadEmojiPicker = function () {
                            if (document.getElementById('emoji-picker-script')) {
                                return Promise.resolve();
                            }
                            return new Promise((resolve) => {
                                const script = document.createElement('script');
                                script.id = 'emoji-picker-script';
                                script.type = 'module';
                                script.src = 'https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js';
                                script.onload = () => resolve();
                                script.onerror = () => resolve();
                                document.head.appendChild(script);
                            });
                        };
                    }
                    return window.loadEmojiPicker();
                },
                async toggleEmojiPicker() {
                    this.showEmojiPicker = ! this.showEmojiPicker;
                    if (! this.showEmojiPicker) {
                        return;
                    }
                    await this.ensureEmojiPickerLoaded();
                    await this.$nextTick();
                    this.initEmojiPicker();
                },
                initEmojiPicker() {
                    const picker = this.$refs.emojiPicker;
                    if (! picker || picker.hasAttribute('data-initialized')) {
                        return;
                    }
                    picker.addEventListener('emoji-click', (event) => {
                        const emoji = event.detail?.unicode || event.detail?.emoji?.unicode;
                        if (! emoji) {
                            return;
                        }
                        this.insertAtCursor(emoji);
                        this.showEmojiPicker = false;
                    });
                    picker.setAttribute('data-initialized', 'true');
                },
                async uploadImage(event) {
                    const file = event.target.files?.[0] ?? event.dataTransfer?.files?.[0];
                    if (! file) {
                        return;
                    }
                    const form = new FormData();
                    form.append('file', file);
                    const response = await fetch('/commentify/api/v1/media', {
                        method: 'POST',
                        body: form,
                        credentials: 'include',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
                        },
                    });
                    if (! response.ok) {
                        return;
                    }
                    const { url } = await response.json();
                    this.insertAtCursor(`![image](${url})`);
                    if (event.target?.value !== undefined) {
                        event.target.value = '';
                    }
                },
                onDrop(event) {
                    this.dragging = false;
                    this.uploadImage(event);
                }
            }"
            x-init="
                $nextTick(() => autoGrow());
                ensureEmojiPickerLoaded();
            "
        >
            @if (config('commentify.enable_markdown_preview', true) || config('commentify.enable_markdown_toolbar', true) || config('commentify.enable_emoji_picker', true))
                <div class="relative z-20 flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-3 py-2 dark:border-gray-700">
                    @if (config('commentify.enable_markdown_preview', true))
                        <div class="flex items-center gap-1" role="tablist" aria-label="{{ __('commentify::commentify.comments.composer_tabs') }}">
                            <button
                                type="button"
                                role="tab"
                                @click="setMode('write')"
                                :aria-selected="mode === 'write'"
                                :class="mode === 'write'
                                    ? 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white'
                                    : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                                class="rounded-md px-2.5 py-1 text-sm font-medium"
                            >
                                {{ __('commentify::commentify.comments.write') }}
                            </button>
                            <button
                                type="button"
                                role="tab"
                                @click="setMode('preview')"
                                :aria-selected="mode === 'preview'"
                                :class="mode === 'preview'
                                    ? 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white'
                                    : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                                class="rounded-md px-2.5 py-1 text-sm font-medium"
                            >
                                {{ __('commentify::commentify.comments.preview') }}
                            </button>
                        </div>
                    @else
                        <div></div>
                    @endif

                    <div class="relative flex flex-wrap items-center gap-0.5" x-show="mode === 'write'">
                        @if (config('commentify.enable_markdown_toolbar', true))
                            <button type="button" @click="insertLinePrefix('### ')" class="{{ $toolbarBtn }} font-semibold" aria-label="{{ __('commentify::commentify.comments.toolbar_heading') }}" title="{{ __('commentify::commentify.comments.toolbar_heading') }}">H</button>
                            <button type="button" @click="wrapSelection('**', '**', '{{ __('commentify::commentify.comments.toolbar_bold_placeholder') }}')" class="{{ $toolbarBtn }} font-bold" aria-label="{{ __('commentify::commentify.comments.toolbar_bold') }}" title="{{ __('commentify::commentify.comments.toolbar_bold') }}">B</button>
                            <button type="button" @click="wrapSelection('*', '*', '{{ __('commentify::commentify.comments.toolbar_italic_placeholder') }}')" class="{{ $toolbarBtn }} italic" aria-label="{{ __('commentify::commentify.comments.toolbar_italic') }}" title="{{ __('commentify::commentify.comments.toolbar_italic') }}">I</button>
                            <button type="button" @click="wrapSelection('~~', '~~', '{{ __('commentify::commentify.comments.toolbar_strike_placeholder') }}')" class="{{ $toolbarBtn }} line-through" aria-label="{{ __('commentify::commentify.comments.toolbar_strike') }}" title="{{ __('commentify::commentify.comments.toolbar_strike') }}">S</button>
                            <button type="button" @click="insertLinePrefix('> ')" class="{{ $toolbarBtn }}" aria-label="{{ __('commentify::commentify.comments.toolbar_quote') }}" title="{{ __('commentify::commentify.comments.toolbar_quote') }}">&quot;</button>
                            <button type="button" @click="wrapSelection('`', '`', '{{ __('commentify::commentify.comments.toolbar_code_placeholder') }}')" class="{{ $toolbarBtn }} font-mono" aria-label="{{ __('commentify::commentify.comments.toolbar_code') }}" title="{{ __('commentify::commentify.comments.toolbar_code') }}">&lt;/&gt;</button>
                            <button type="button" @click="insertCodeBlock()" class="{{ $toolbarBtn }} font-mono text-xs" aria-label="{{ __('commentify::commentify.comments.toolbar_code_block') }}" title="{{ __('commentify::commentify.comments.toolbar_code_block') }}">{ }</button>
                            <button type="button" @click="wrapSelection('[', '](https://)', '{{ __('commentify::commentify.comments.toolbar_link_placeholder') }}')" class="{{ $toolbarBtn }}" aria-label="{{ __('commentify::commentify.comments.toolbar_link') }}" title="{{ __('commentify::commentify.comments.toolbar_link') }}">Link</button>
                            <button type="button" @click="insertLinePrefix('- ')" class="{{ $toolbarBtn }}" aria-label="{{ __('commentify::commentify.comments.toolbar_ul') }}" title="{{ __('commentify::commentify.comments.toolbar_ul') }}">•</button>
                            <button type="button" @click="insertLinePrefix('1. ')" class="{{ $toolbarBtn }}" aria-label="{{ __('commentify::commentify.comments.toolbar_ol') }}" title="{{ __('commentify::commentify.comments.toolbar_ol') }}">1.</button>
                            <button type="button" @click="insertLinePrefix('- [ ] ')" class="{{ $toolbarBtn }}" aria-label="{{ __('commentify::commentify.comments.toolbar_task') }}" title="{{ __('commentify::commentify.comments.toolbar_task') }}">☐</button>
                            <button type="button" @click="insertMention()" class="{{ $toolbarBtn }}" aria-label="{{ __('commentify::commentify.comments.toolbar_mention') }}" title="{{ __('commentify::commentify.comments.toolbar_mention') }}">@</button>
                            @if (config('commentify-pro.media.enabled', false))
                                <label class="{{ $toolbarBtn }} cursor-pointer" title="{{ __('commentify::commentify.comments.toolbar_image') }}">
                                    {{ __('commentify::commentify.comments.toolbar_image') }}
                                    <input type="file" accept="image/*" class="hidden" x-on:change="uploadImage($event)" />
                                </label>
                            @endif
                            <button type="button" @click="insertAtCursor('\n\n---\n\n')" class="{{ $toolbarBtn }}" aria-label="{{ __('commentify::commentify.comments.toolbar_hr') }}" title="{{ __('commentify::commentify.comments.toolbar_hr') }}">—</button>
                        @endif

                        @if (config('commentify.enable_emoji_picker', true))
                            <div class="relative">
                                <button
                                    type="button"
                                    @click.stop="toggleEmojiPicker()"
                                    class="rounded p-1.5 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                    title="{{ __('commentify::commentify.comments.add_emoji') }}"
                                    aria-label="{{ __('commentify::commentify.comments.add_emoji') }}"
                                    :aria-expanded="showEmojiPicker"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                                <div
                                    x-show="showEmojiPicker"
                                    x-cloak
                                    @click.outside="showEmojiPicker = false"
                                    @click.stop
                                    class="absolute right-0 top-full z-[100] mt-2"
                                    style="width: 352px; max-width: min(352px, calc(100vw - 2rem));"
                                >
                                    <style>
                                        emoji-picker {
                                            --background: white;
                                            --border-color: rgb(229, 231, 235);
                                            --num-columns: 8;
                                            --category-emoji-size: 1.5rem;
                                            --emoji-size: 1.75rem;
                                            border-radius: 0.5rem;
                                            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                                            width: 100%;
                                        }
                                        .dark emoji-picker {
                                            --background: rgb(17, 24, 39);
                                            --border-color: rgb(55, 65, 81);
                                            --text-color: rgb(243, 244, 246);
                                        }
                                    </style>
                                    <emoji-picker x-ref="emojiPicker" id="emoji-picker-{{ $inputId }}"></emoji-picker>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="px-4 py-2">
                <label for="{{ $inputId }}" class="sr-only">{{ $inputLabel }}</label>
                <textarea
                    x-ref="textarea"
                    x-show="mode === 'write'"
                    id="{{ $inputId }}"
                    rows="6"
                    class="w-full resize-y border-0 bg-transparent px-0 text-sm text-gray-900 focus:outline-none focus:ring-0 dark:bg-transparent dark:text-white dark:placeholder-gray-400 @error($state.'.body') border-red-500 @enderror"
                    placeholder="{{ __('commentify::commentify.comments.write_comment') }}"
                    wire:model.live="{{ $state }}.body"
                    @input="detectAtSymbol"
                ></textarea>

                @if (config('commentify.enable_markdown_preview', true))
                    <div
                        x-show="mode === 'preview'"
                        x-cloak
                        class="prose prose-sm dark:prose-invert min-h-[9rem] max-w-none text-sm text-gray-900 dark:text-gray-100"
                    >
                        <template x-if="previewLoading">
                            <p class="text-gray-500 dark:text-gray-400">{{ __('commentify::commentify.comments.preview_loading') }}</p>
                        </template>
                        <div x-show="!previewLoading" x-html="previewHtml"></div>
                    </div>
                @endif
            </div>
        </div>

        @if (! empty($users) && $users->count() > 0)
            @include('commentify::livewire.partials.dropdowns.users')
        @endif
        @error($state.'.body')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ $message }}
            </p>
        @enderror

        @if (config('commentify-pro.notifications.subscriptions_enabled', false) && config('commentify.enable_notifications', false))
            <label class="mb-4 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <input type="checkbox" wire:model="subscribe_to_replies" class="rounded border-gray-300 dark:border-gray-600" />
                {{ __('commentify::commentify.comments.subscribe_to_replies') }}
            </label>
        @endif

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
    <div class="italic text-gray-500 dark:text-gray-400">{{ __('commentify::commentify.comments.read_only_message') }}</div>
@endif
