@props([
    'href' => url()->previous(),
    'title' => '',
])

<a href="{{ $href }}"
    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 hover:bg-blue-200 hover:text-blue-900 font-medium rounded-full shadow-sm transition-all duration-200">
    {{-- Flecha hacia la izquierda moderna --}}
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
        <path d="M15 18l-6-6 6-6" />
    </svg>
    {{-- <span>{{ $title }}</span> --}}
</a>
