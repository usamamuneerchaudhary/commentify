<div class="card position-absolute top-100 start-0 mt-1 shadow" style="width: 240px; max-height: 200px; z-index: 1050;">
    <ul class="list-group list-group-flush overflow-auto" style="max-height: 200px;">
        @foreach($users as $user)
            <li wire:click="selectUser('{{ $user->name }}')" wire:key="{{ $user->id }}" class="list-group-item list-group-item-action cursor-pointer">
                <div class="d-flex align-items-center">
                    <img class="rounded-circle me-2" src="{{$user->avatar()}}" alt="{{ $user->name }}" style="width: 24px; height: 24px;">
                    <span>{{ $user->name }}</span>
                </div>
            </li>
        @endforeach
    </ul>
</div>

