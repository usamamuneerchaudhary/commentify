@php
    $record = $getRecord();
@endphp

@if ($record instanceof \Illuminate\Database\Eloquent\Model && $record->exists)
    <x-commentify::thread :model="$record" />
@endif
