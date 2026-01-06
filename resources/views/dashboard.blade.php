<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }} - Recursos Humanos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Bienvenido a Bigmat - Sistema de Recursos Humanos</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
                        <!-- Usuarios -->
                        <a href="{{ route('users.index') }}" class="block p-6 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                            <h4 class="font-semibold text-blue-700">Usuarios</h4>
                            <p class="text-sm text-gray-600">Gestionar empleados</p>
                        </a>

                        <!-- Vacaciones -->
                        <a href="{{ route('vacaciones.index') }}" class="block p-6 bg-green-50 rounded-lg hover:bg-green-100 transition">
                            <h4 class="font-semibold text-green-700">Vacaciones</h4>
                            <p class="text-sm text-gray-600">Control de vacaciones</p>
                        </a>

                        <!-- Turnos -->
                        <a href="{{ route('asignaciones-turnos.index') }}" class="block p-6 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                            <h4 class="font-semibold text-yellow-700">Turnos</h4>
                            <p class="text-sm text-gray-600">Asignaciones de turnos</p>
                        </a>

                        <!-- Nominas -->
                        <a href="{{ route('nominas.index') }}" class="block p-6 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                            <h4 class="font-semibold text-purple-700">Nominas</h4>
                            <p class="text-sm text-gray-600">Gestion de nominas</p>
                        </a>

                        <!-- Incorporaciones -->
                        <a href="{{ route('incorporaciones.index') }}" class="block p-6 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                            <h4 class="font-semibold text-indigo-700">Incorporaciones</h4>
                            <p class="text-sm text-gray-600">Nuevos empleados</p>
                        </a>

                        <!-- EPIs -->
                        <a href="{{ route('epis.index') }}" class="block p-6 bg-red-50 rounded-lg hover:bg-red-100 transition">
                            <h4 class="font-semibold text-red-700">EPIs</h4>
                            <p class="text-sm text-gray-600">Equipos de proteccion</p>
                        </a>

                        <!-- Departamentos -->
                        <a href="{{ route('departamentos.index') }}" class="block p-6 bg-teal-50 rounded-lg hover:bg-teal-100 transition">
                            <h4 class="font-semibold text-teal-700">Departamentos</h4>
                            <p class="text-sm text-gray-600">Estructura organizativa</p>
                        </a>

                        <!-- Festivos -->
                        <a href="{{ route('festivos.index') }}" class="block p-6 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                            <h4 class="font-semibold text-orange-700">Festivos</h4>
                            <p class="text-sm text-gray-600">Calendario laboral</p>
                        </a>

                        <!-- Empresas -->
                        <a href="{{ route('empresas.index') }}" class="block p-6 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <h4 class="font-semibold text-gray-700">Configuracion</h4>
                            <p class="text-sm text-gray-600">Empresas y fiscalidad</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
