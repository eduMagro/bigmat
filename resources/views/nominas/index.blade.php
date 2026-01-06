<x-app-layout>
    <x-slot name="title">Nominas</x-slot>

    <div class="py-6 px-4">
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                Importar Nominas
            </h1>
            <!-- Formulario -->
            <form action="{{ route('nominas.dividir') }}" method="POST" enctype="multipart/form-data" class="space-y-4"
                x-data="{ cargando: false }" @submit="cargando = true">
                @csrf
                <!-- Seleccion de mes -->

                <div class="max-w-xs">
                    <label for="mes_anio" class="block text-sm font-medium text-gray-700 mb-1">
                        Mes y ano de las nominas
                    </label>
                    <input type="month" name="mes_anio" id="mes_anio" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    @error('mes_anio')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Archivo PDF -->
                <div class="max-w-xs">
                    <label for="archivo" class="block text-sm font-medium text-gray-700 mb-1">
                        Selecciona el PDF con las nominas
                    </label>
                    <input type="file" name="archivo" id="archivo" accept=".pdf" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    @error('archivo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
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
