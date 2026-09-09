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
        @include('commentify::livewire.partials.guest-fields', [
            'showGuestFields' => config('commentify.allow_guests', false) && auth()->guest(),
        ])
        <div
            class="card mb-3 position-relative"
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
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 position-relative" style="z-index: 20;">
                    @if (config('commentify.enable_markdown_preview', true))
                        <div class="btn-group btn-group-sm" role="tablist" aria-label="{{ __('commentify::commentify.comments.composer_tabs') }}">
                            <button
                                type="button"
                                class="btn"
                                role="tab"
                                @click="setMode('write')"
                                :class="mode === 'write' ? 'btn-secondary' : 'btn-outline-secondary'"
                                :aria-selected="mode === 'write'"
                            >
                                {{ __('commentify::commentify.comments.write') }}
                            </button>
                            <button
                                type="button"
                                class="btn"
                                role="tab"
                                @click="setMode('preview')"
                                :class="mode === 'preview' ? 'btn-secondary' : 'btn-outline-secondary'"
                                :aria-selected="mode === 'preview'"
                            >
                                {{ __('commentify::commentify.comments.preview') }}
                            </button>
                        </div>
                    @else
                        <div></div>
                    @endif

                    <div class="d-flex flex-wrap align-items-center gap-1 position-relative" x-show="mode === 'write'">
                        @if (config('commentify.enable_markdown_toolbar', true))
                            <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold" @click="insertLinePrefix('### ')" aria-label="{{ __('commentify::commentify.comments.toolbar_heading') }}" title="{{ __('commentify::commentify.comments.toolbar_heading') }}">H</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" @click="wrapSelection('**', '**', '{{ __('commentify::commentify.comments.toolbar_bold_placeholder') }}')" aria-label="{{ __('commentify::commentify.comments.toolbar_bold') }}" title="{{ __('commentify::commentify.comments.toolbar_bold') }}">B</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary fst-italic" @click="wrapSelection('*', '*', '{{ __('commentify::commentify.comments.toolbar_italic_placeholder') }}')" aria-label="{{ __('commentify::commentify.comments.toolbar_italic') }}" title="{{ __('commentify::commentify.comments.toolbar_italic') }}">I</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary text-decoration-line-through" @click="wrapSelection('~~', '~~', '{{ __('commentify::commentify.comments.toolbar_strike_placeholder') }}')" aria-label="{{ __('commentify::commentify.comments.toolbar_strike') }}" title="{{ __('commentify::commentify.comments.toolbar_strike') }}">S</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insertLinePrefix('> ')" aria-label="{{ __('commentify::commentify.comments.toolbar_quote') }}" title="{{ __('commentify::commentify.comments.toolbar_quote') }}">&quot;</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" @click="wrapSelection('`', '`', '{{ __('commentify::commentify.comments.toolbar_code_placeholder') }}')" aria-label="{{ __('commentify::commentify.comments.toolbar_code') }}" title="{{ __('commentify::commentify.comments.toolbar_code') }}">&lt;/&gt;</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" @click="insertCodeBlock()" aria-label="{{ __('commentify::commentify.comments.toolbar_code_block') }}" title="{{ __('commentify::commentify.comments.toolbar_code_block') }}">{ }</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="wrapSelection('[', '](https://)', '{{ __('commentify::commentify.comments.toolbar_link_placeholder') }}')" aria-label="{{ __('commentify::commentify.comments.toolbar_link') }}" title="{{ __('commentify::commentify.comments.toolbar_link') }}">Link</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insertLinePrefix('- ')" aria-label="{{ __('commentify::commentify.comments.toolbar_ul') }}" title="{{ __('commentify::commentify.comments.toolbar_ul') }}">•</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insertLinePrefix('1. ')" aria-label="{{ __('commentify::commentify.comments.toolbar_ol') }}" title="{{ __('commentify::commentify.comments.toolbar_ol') }}">1.</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insertLinePrefix('- [ ] ')" aria-label="{{ __('commentify::commentify.comments.toolbar_task') }}" title="{{ __('commentify::commentify.comments.toolbar_task') }}">☐</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insertMention()" aria-label="{{ __('commentify::commentify.comments.toolbar_mention') }}" title="{{ __('commentify::commentify.comments.toolbar_mention') }}">@</button>
                            @if (config('commentify-pro.media.enabled', false))
                                <label class="btn btn-sm btn-outline-secondary mb-0" title="{{ __('commentify::commentify.comments.toolbar_image') }}">
                                    {{ __('commentify::commentify.comments.toolbar_image') }}
                                    <input type="file" accept="image/*" class="d-none" x-on:change="uploadImage($event)" />
                                </label>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="insertAtCursor('\n\n---\n\n')" aria-label="{{ __('commentify::commentify.comments.toolbar_hr') }}" title="{{ __('commentify::commentify.comments.toolbar_hr') }}">—</button>
                        @endif

                        @if (config('commentify.enable_emoji_picker', true))
                            <div class="position-relative">
                                <button
                                    type="button"
                                    @click.stop="toggleEmojiPicker()"
                                    class="btn btn-sm btn-outline-secondary"
                                    title="{{ __('commentify::commentify.comments.add_emoji') }}"
                                    aria-label="{{ __('commentify::commentify.comments.add_emoji') }}"
                                    :aria-expanded="showEmojiPicker"
                                >
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                                <div
                                    x-show="showEmojiPicker"
                                    x-cloak
                                    @click.outside="showEmojiPicker = false"
                                    @click.stop
                                    class="position-absolute top-100 end-0 mt-2"
                                    style="width: 352px; max-width: min(352px, calc(100vw - 2rem)); z-index: 1080;"
                                >
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
                                            width: 100%;
                                        }
                                        [data-bs-theme="dark"] emoji-picker {
                                            --background: var(--bs-body-bg);
                                            --border-color: var(--bs-border-color);
                                            --text-color: var(--bs-body-color);
                                        }
                                    </style>
                                    <emoji-picker x-ref="emojiPicker" id="emoji-picker-{{ $inputId }}"></emoji-picker>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card-body">
                <label for="{{ $inputId }}" class="form-label visually-hidden">{{ $inputLabel }}</label>
                <textarea
                    x-ref="textarea"
                    x-show="mode === 'write'"
                    id="{{ $inputId }}"
                    rows="4"
                    class="form-control @error($state.'.body') is-invalid @enderror"
                    placeholder="{{ __('commentify::commentify.comments.write_comment') }}"
                    wire:model.live="{{ $state }}.body"
                    @input="detectAtSymbol"
                ></textarea>

                @if (config('commentify.enable_markdown_preview', true))
                    <div
                        x-show="mode === 'preview'"
                        x-cloak
                        class="border rounded p-3 bg-body-tertiary"
                        style="min-height: 9rem;"
                    >
                        <template x-if="previewLoading">
                            <p class="text-muted mb-0">{{ __('commentify::commentify.comments.preview_loading') }}</p>
                        </template>
                        <div x-show="!previewLoading" x-html="previewHtml"></div>
                    </div>
                @endif

                @if (! empty($users) && $users->count() > 0)
                    @include('commentify::livewire.partials.dropdowns.users')
                @endif
                @error($state.'.body')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror

                @if (config('commentify-pro.notifications.subscriptions_enabled', false) && config('commentify.enable_notifications', false))
                    <div class="form-check mb-3">
                        <input type="checkbox" wire:model="subscribe_to_replies" class="form-check-input" id="{{ $inputId }}-subscribe">
                        <label class="form-check-label" for="{{ $inputId }}-subscribe">
                            {{ __('commentify::commentify.comments.subscribe_to_replies') }}
                        </label>
                    </div>
                @endif
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
