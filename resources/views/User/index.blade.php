<x-app-layout>
    <x-slot name="title">Usuarios - {{ config('app.name') }}</x-slot>

    @livewire('users-table')
    @livewire('users-table-mobile')
</x-app-layout>