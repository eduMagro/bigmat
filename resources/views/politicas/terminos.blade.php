<x-guest-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <a href="{{ url()->previous() }}" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mb-4">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </a>
            </div>

            <!-- Content -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
                <div class="prose prose-blue dark:prose-invert max-w-none">
                    @include('politicas.contenido.terminos')
                </div>
            </div>

            <!-- Footer Links -->
            <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                <div class="flex justify-center gap-4 flex-wrap">
                    <a href="{{ route('politicas.privacidad') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Politica de Privacidad</a>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <a href="{{ route('politicas.cookies') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Politica de Cookies</a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
