<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <x-volver />{{ __('Administración de Perfil') }}
        </h2>

    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
            {{-- Cerrar sesiones en otros dispositivos --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl space-y-4">
                    <form method="POST" action="{{ route('usuarios.cerrarSesiones', $user) }}">
                        @csrf
                        <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                            🛑 Cerrar sesiones activas
                        </button>
                    </form>
                    @if ($sesiones->isNotEmpty())
                        <div class="mt-4 space-y-2 text-sm text-gray-700">
                            <h3 class="font-semibold text-base text-gray-800">Sesiones activas de
                                {{ $user->nombre_completo }}:</h3>

                            @foreach ($sesiones as $sesion)
                                <div class="p-3 border rounded bg-gray-50">
                                    <p><strong>IP:</strong> {{ $sesion['ip_address'] ?? 'Desconocida' }}</p>
                                    <p><strong>Navegador:</strong> {{ Str::limit($sesion['user_agent'], 80) }}</p>
                                    <p><strong>Última actividad:</strong> {{ $sesion['ultima_actividad'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 mt-4">Este usuario no tiene sesiones activas.</p>
                    @endif

                </div>
            </div>
            {{-- 🧑‍💼 Despedir al trabajador --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl space-y-4">

                    @if ($user->estado === 'despedido')
                        <div class="text-red-700 bg-red-50 border border-red-200 p-3 rounded mt-4 text-sm">
                            Este usuario ha sido <strong>despedido</strong> digitalmente el
                            {{ $user->fecha_baja ?? 'fecha desconocida' }}.
                        </div>
                    @else
                        <div class="text-yellow-800 bg-yellow-50 border border-yellow-200 p-3 rounded text-sm">
                            <strong>⚠️ ¿Qué implica despedir digitalmente a un usuario?</strong><br>
                            Esta acción realiza los siguientes cambios de forma automática:
                            <ul class="list-disc pl-5 mt-2">
                                <li>Revoca su acceso a la plataforma</li>
                                <li>Cierra sus sesiones activas</li>
                                <li>Cancela sus turnos futuros</li>
                                <li>Anula movimientos pendientes asignados</li>
                                <li>Guarda la fecha de baja y lo marca como despedido</li>
                            </ul>
                            <span class="text-xs text-gray-500">* Esta acción es irreversible desde la interfaz.</span>
                        </div>

                        <form id="form-despedir-usuario" method="POST"
                            action="{{ route('usuarios.editarDespedir', $user) }}">
                            @csrf
                            <button type="button" onclick="confirmarDespido()"
                                class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-2 px-4 rounded mt-3">
                                ⚠️ Despedir trabajador
                            </button>
                        </form>
                    @endif

                </div>
            </div>

            {{-- Script para SweetAlert --}}
            <script>
                function confirmarDespido() {
                    Swal.fire({
                        title: '¿Despedir al trabajador?',
                        text: "Esta acción desactivará su cuenta y cancelará sus tareas.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, despedir',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('form-despedir-usuario').submit();
                        }
                    });
                }
            </script>


            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
