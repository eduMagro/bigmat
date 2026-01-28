<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }} - Recursos Humanos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">Bienvenido a Bigmat - Sistema de Recursos Humanos</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
                        <!-- Usuarios -->
                        <a href="{{ route('users.index') }}" class="block p-6 bg-blue-50 dark:bg-blue-900/30 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition">
                            <h4 class="font-semibold text-blue-700 dark:text-blue-400">Usuarios</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Gestionar empleados</p>
                        </a>

                        <!-- Vacaciones -->
                        <a href="{{ route('vacaciones.index') }}" class="block p-6 bg-green-50 dark:bg-green-900/30 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/50 transition">
                            <h4 class="font-semibold text-green-700 dark:text-green-400">Vacaciones</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Control de vacaciones</p>
                        </a>

                        <!-- Turnos -->
                        <a href="{{ route('asignaciones-turnos.index') }}" class="block p-6 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/50 transition">
                            <h4 class="font-semibold text-yellow-700 dark:text-yellow-400">Turnos</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Asignaciones de turnos</p>
                        </a>

                        <!-- Nominas -->
                        <a href="{{ route('nominas.index') }}" class="block p-6 bg-purple-50 dark:bg-purple-900/30 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/50 transition">
                            <h4 class="font-semibold text-purple-700 dark:text-purple-400">Nominas</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Gestion de nominas</p>
                        </a>

                        <!-- Incorporaciones -->
                        <a href="{{ route('incorporaciones.index') }}" class="block p-6 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition">
                            <h4 class="font-semibold text-indigo-700 dark:text-indigo-400">Incorporaciones</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Nuevos empleados</p>
                        </a>

                        <!-- EPIs -->
                        <a href="{{ route('epis.index') }}" class="block p-6 bg-red-50 dark:bg-red-900/30 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition">
                            <h4 class="font-semibold text-red-700 dark:text-red-400">EPIs</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Equipos de proteccion</p>
                        </a>

                        <!-- Departamentos -->
                        <a href="{{ route('departamentos.index') }}" class="block p-6 bg-teal-50 dark:bg-teal-900/30 rounded-lg hover:bg-teal-100 dark:hover:bg-teal-900/50 transition">
                            <h4 class="font-semibold text-teal-700 dark:text-teal-400">Departamentos</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Estructura organizativa</p>
                        </a>

                        <!-- Festivos -->
                        <a href="{{ route('festivos.index') }}" class="block p-6 bg-orange-50 dark:bg-orange-900/30 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/50 transition">
                            <h4 class="font-semibold text-orange-700 dark:text-orange-400">Festivos</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Calendario laboral</p>
                        </a>

                        <!-- Empresas -->
                        <a href="{{ route('empresas.index') }}" class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <h4 class="font-semibold text-gray-700 dark:text-gray-300">Configuracion</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Empresas y fiscalidad</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
