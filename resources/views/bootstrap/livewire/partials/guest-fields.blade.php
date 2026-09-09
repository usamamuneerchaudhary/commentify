@if ($showGuestFields ?? false)
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="{{ $inputId }}-guest-name" class="form-label">
                {{ __('commentify::commentify.comments.guest_name') }}
            </label>
            <input
                type="text"
                id="{{ $inputId }}-guest-name"
                wire:model="guest_name"
                class="form-control @error('guest_name') is-invalid @enderror"
                placeholder="{{ __('commentify::commentify.comments.guest_name_placeholder') }}"
            />
            @error('guest_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label for="{{ $inputId }}-guest-email" class="form-label">
                {{ __('commentify::commentify.comments.guest_email') }}
            </label>
            <input
                type="email"
                id="{{ $inputId }}-guest-email"
                wire:model="guest_email"
                class="form-control @error('guest_email') is-invalid @enderror"
                placeholder="{{ __('commentify::commentify.comments.guest_email_placeholder') }}"
            />
            @error('guest_email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
@endif
