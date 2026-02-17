<div>
    @if($isEditing)
        @include('commentify::livewire.partials.comment-form',[
            'method'=>'editComment',
            'state'=>'editState',
            'inputId'=> 'reply-comment',
            'inputLabel'=> __('commentify::commentify.comments.your_reply'),
            'button'=> __('commentify::commentify.comments.edit_comment')
        ])
    @else
        <article class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <img class="rounded-circle" src="{{$comment->user->avatar()}}" alt="{{$comment->user->name}}" style="width: 32px; height: 32px;">
                        <div>
                            <strong class="d-block">{{Str::ucfirst($comment->user->name)}}</strong>
                            <small class="text-muted">
                                <time pubdate datetime="{{$comment->presenter()->relativeCreatedAt()}}" title="{{$comment->presenter()->relativeCreatedAt()}}">
                                    {{$comment->presenter()->relativeCreatedAt()}}
                                </time>
                            </small>
                        </div>
                    </div>
                    <div class="dropdown position-relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="btn btn-sm btn-link text-muted p-1"
                                type="button"
                                :class="{ 'show': open }"
                                :aria-expanded="open">
                            <svg class="bi" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                            </svg>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow"
                            x-show="open"
                            @click.away="open = false"
                            x-cloak
                            :class="{ 'show': open }"
                            style="display: none;">
                                @can('update',$comment)
                                    <li>
                                        <button wire:click="$toggle('isEditing')" type="button" class="dropdown-item">
                                            {{ __('commentify::commentify.comments.edit') }}
                                        </button>
                                    </li>
                                @endcan
                                @can('destroy',$comment)
                                    <li>
                                        <button x-on:click="confirmCommentDeletion"
                                                x-data="{ confirmCommentDeletion(){ if(window.confirm('{{ __('commentify::commentify.comments.delete_confirm') }}')){ @this.call('deleteComment') } } }"
                                                class="dropdown-item text-danger">
                                            {{ __('commentify::commentify.comments.delete') }}
                                        </button>
                                    </li>
                                @endcan
                                @if(config('commentify.enable_reporting', true))
                                    @php
                                        $isOwnComment = auth()->check() && auth()->id() == $comment->user_id;
                                    @endphp
                                    @if(!$isOwnComment)
                                        <li>
                                            <button wire:click="showReportForm" type="button" class="dropdown-item text-danger">
                                                {{ __('commentify::commentify.comments.report') }}
                                            </button>
                                        </li>
                                    @endif
                                @endif
                            </ul>
                    </div>
                </div>
                <div class="mb-3">
                    {!! $comment->presenter()->replaceUserMentions($comment->presenter()->markdownBody()) !!}
                </div>
                <div class="d-flex align-items-center gap-3">
                    <livewire:like :$comment :key="$comment->id"/>
                    @include('commentify::livewire.partials.comment-reply')
                </div>
            </div>
        </article>
    @endif
    @if($isReplying)
        @include('commentify::livewire.partials.comment-form',[
           'method'=>'postReply',
           'state'=>'replyState',
           'inputId'=> 'reply-comment',
           'inputLabel'=> __('commentify::commentify.comments.your_reply'),
           'button'=> __('commentify::commentify.comments.post_reply')
       ])
    @endif
    @if($isReporting)
        <div class="card mb-3 border-warning">
            <div class="card-body">
                @if($alreadyReported)
                    <div class="text-center py-3">
                        <p class="text-muted mb-3">{{ __('commentify::commentify.comments.already_reported') }}</p>
                        <button type="button" wire:click="closeReportForm" class="btn btn-secondary btn-sm">
                            {{ __('commentify::commentify.comments.close') }}
                        </button>
                    </div>
                @else
                    <h5 class="card-title mb-3">{{ __('commentify::commentify.comments.report_comment') }}</h5>
                    <form wire:submit.prevent="reportComment">
                        <div class="mb-3">
                            <label class="form-label">{{ __('commentify::commentify.comments.report_reason') }}</label>
                            @php
                                $reportReasons = config('commentify.report_reasons', ['spam', 'inappropriate', 'offensive', 'other']);
                            @endphp
                            <div class="list-group">
                                @foreach($reportReasons as $reason)
                                    <label class="list-group-item list-group-item-action">
                                        <input type="radio" wire:model="reportState.reason" value="{{ $reason }}" class="form-check-input me-2">
                                        {{ __('commentify::commentify.comments.report_reason_' . $reason) }}
                                    </label>
                                @endforeach
                            </div>
                            @error('reportState.reason')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        @php
                            $hasOtherReason = in_array('other', config('commentify.report_reasons', []));
                        @endphp
                        @if($hasOtherReason)
                            <div class="mb-3" x-show="$wire.reportState.reason === 'other'" x-cloak>
                                <label for="report-additional-details" class="form-label">
                                    {{ __('commentify::commentify.comments.additional_details') }}
                                </label>
                                <textarea wire:model.blur="reportState.additional_details" id="report-additional-details" rows="3" class="form-control" placeholder="{{ __('commentify::commentify.comments.additional_details_placeholder') }}"></textarea>
                                @error('reportState.additional_details')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" wire:click="$set('isReporting', false)" wire:loading.attr="disabled" class="btn btn-secondary btn-sm">
                                {{ __('commentify::commentify.comments.cancel') }}
                            </button>
                            <button type="submit" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                                <span wire:loading wire:target="reportComment" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                <span wire:loading.remove wire:target="reportComment">
                                    {{ __('commentify::commentify.comments.submit_report') }}
                                </span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
    @if($hasReplies)
        <div class="ms-4 ps-3 border-start">
            @foreach($comment->children as $child)
                <livewire:comment :comment="$child" :key="$child->id"/>
            @endforeach
        </div>
    @endif
</div>

