<x-app-layout>
    <x-slot name="title">Nominas</x-slot>

    <div class="py-6 px-4">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                Importar Nominas
            </h1>
            <!-- Formulario -->
            <form action="{{ route('nominas.dividir') }}" method="POST" enctype="multipart/form-data" class="space-y-4"
                x-data="{ cargando: false }" @submit="cargando = true">
                @csrf
                <!-- Seleccion de mes -->

                <div class="max-w-xs">
                    <label for="mes_anio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Mes y ano de las nominas
                    </label>
                    <input type="month" name="mes_anio" id="mes_anio" required
                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:focus:ring-blue-800 focus:ring-opacity-50">
                    @error('mes_anio')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Archivo PDF -->
                <div class="max-w-xs">
                    <label for="archivo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Selecciona el PDF con las nominas
                    </label>
                    <input type="file" name="archivo" id="archivo" accept=".pdf" required
                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:focus:ring-blue-800 focus:ring-opacity-50 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 dark:file:bg-blue-900 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-800">
                    @error('archivo')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Boton -->
                <div>
                    <x-boton-submit texto="Importar Nominas" color="blue" :cargando="true" />
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
