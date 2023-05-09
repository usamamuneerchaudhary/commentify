<div class="z-10 bg-white rounded-lg shadow w-60 dark:bg-gray-700">
    <ul class="h-48 py-2 overflow-y-auto text-gray-700 dark:text-gray-200"
    >
        @foreach($users as $user)
            <li wire:click="selectUser('{{ $user->name }}')" wire:key="{{ $user->id }}">
                <a class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                    <img class="w-6 h-6 mr-2 rounded-full" src="{{$user->avatar()}}"
                         alt="{{ $user->name }}">
                    {{ $user->name }}
                </a>
            </li>
        @endforeach

    </ul>
</div>
