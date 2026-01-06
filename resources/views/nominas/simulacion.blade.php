<x-app-layout>
    <x-slot name="title">Usuarios - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ 'Simulador Nómina' }}
        </h2>
    </x-slot>
    <div class="max-w-7xl mx-auto p-6">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Formulario desde bruto --}}
            <div class="bg-white p-6 rounded shadow">
                <h3 class="text-lg font-semibold mb-4 text-gray-700">Desde salario bruto</h3>
                <form id="formBruto" method="POST" action="{{ route('nomina.simular') }}">
                    @csrf

                    <label class="block mb-1 font-semibold">Sueldo bruto anual (€):</label>
                    <input type="number" name="sueldo_bruto_anual" class="form-control" required step="0.01">

                    @include('nominas._form_datos_personales')
                    <br>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
                        Calcular neto
                    </button>
                </form>
            </div>

            {{-- Formulario desde neto --}}
            <div class="bg-white p-6 rounded shadow">
                <h3 class="text-lg font-semibold mb-4 text-gray-700">Desde salario neto</h3>
                <form id="formNeto" method="POST" action="{{ route('nomina.inversa.calcular') }}">
                    @csrf

                    <label class="block mb-1 font-semibold">Sueldo neto mensual deseado (€):</label>
                    <input type="number" name="neto_deseado" class="form-control" required step="0.01">

                    @include('nominas._form_datos_personales')
                    <br>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded">
                        Calcular bruto necesario
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // interceptar ambos formularios
        document.getElementById('formBruto').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            Swal.fire({
                title: 'Resultado desde bruto',
                html: `
                <b>Salario mensual bruto:</b> ${data.bruto_mensual} €<br>
                <b>Seguridad Social:</b> ${data.ss_mensual} €<br>
                <b>IRPF:</b> ${data.retencion_mensual} €<br>
                <b><span class="text-green-600">Neto mensual estimado:</span></b> ${data.neto_mensual} €
            `,
                icon: 'info'
            });
        });

        document.getElementById('formNeto').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            Swal.fire({
                title: 'Resultado desde neto',
                html: `
                <b>Salario bruto anual necesario:</b> ${data.bruto_anual} €<br>
                <b>Bruto mensual:</b> ${data.bruto_mensual} €<br>
                <b>SS estimada:</b> ${data.ss_mensual} €<br>
                <b>IRPF:</b> ${data.retencion_mensual} €<br>
                <b><span class="text-green-600">Neto mensual:</span></b> ${data.neto_mensual} €
            `,
                icon: 'success'
            });
        });
    </script>
</x-app-layout>
