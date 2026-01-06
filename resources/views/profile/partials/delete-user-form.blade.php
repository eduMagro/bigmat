<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Eliminar cuenta') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Una vez que tu cuenta sea eliminada, todos sus recursos y datos serán eliminados permanentemente. Antes de eliminar tu cuenta, por favor descarga cualquier dato o información que desees conservar.') }}
        </p>
    </header>

    <!-- Botón principal de eliminar cuenta -->
    <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="bg-red-600 hover:bg-red-700 focus:bg-red-800 active:bg-red-900 text-white rounded-lg shadow-md transition-all duration-200">
        {{ __('Eliminar cuenta') }}
    </x-danger-button>

    <!-- Modal de confirmación -->

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy', $user->id) }}" class="p-6">

            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('¿Estás seguro de querer borrar tu cuenta?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Una vez que tu cuenta sea eliminada, todos sus recursos y datos serán eliminados permanentemente. Por favor, ingresa tu contraseña para confirmar que deseas eliminar tu cuenta de forma permanente.') }}
            </p>


            <!-- Campo de contraseña -->

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Contraseña') }}" class="sr-only" />

                <x-text-input id="password" name="password" type="password"
                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200 placeholder-gray-400 text-gray-700 focus:outline-none"
                    placeholder="{{ __('Contraseña') }}" />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2 text-red-500" />
            </div>

            <!-- Botones de acción -->
            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')"
                    class="bg-gray-300 hover:bg-gray-400 focus:bg-gray-500 active:bg-gray-600 text-gray-700 rounded-lg shadow-md transition-all duration-200">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-danger-button
                    class="ms-3 bg-red-600 hover:bg-red-700 focus:bg-red-800 active:bg-red-900 text-white rounded-lg shadow-md transition-all duration-200">

                    {{ __('Eliminar cuenta') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
