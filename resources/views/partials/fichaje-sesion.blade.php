{{-- Ayudas comunes de los botones de fichaje: sesión caducada y errores de conexión --}}
<script>
    (function() {
        const DURACION_SESION_MS = {{ (int) config('session.lifetime', 120) }} * 60 * 1000;
        const paginaCargada = Date.now();

        // Sesión caducada: se recarga sola, sin pedírselo al usuario
        window.recargarPorSesionCaducada = function() {
            if (window.Swal) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sesión caducada',
                    text: 'Actualizando la página...',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                });
            }
            setTimeout(() => window.location.reload(), 1200);
        };

        // Devuelve los datos JSON de la respuesta de /fichar. Si la sesión ha caducado
        // recarga la página; si llega algo que no es JSON lanza un error legible.
        window.leerRespuestaFichaje = function(r) {
            if (r.status === 419 || r.status === 401 || r.redirected) {
                window.recargarPorSesionCaducada();
                return new Promise(() => {}); // la página se recarga: no se continúa
            }
            const esJson = (r.headers.get('content-type') || '').includes('application/json');
            if (!esJson) {
                throw new Error(`El servidor ha devuelto un error (código ${r.status}). Vuelve a intentarlo en unos minutos.`);
            }
            return r.json();
        };

        window.mensajeErrorFichaje = function(err) {
            if (err instanceof TypeError) {
                return 'No hay conexión con el servidor. Comprueba la cobertura o el wifi y vuelve a intentarlo.';
            }
            return (err && err.message) || 'No se pudo comunicar con el servidor';
        };

        // Prevención: al volver a una pestaña que lleva abierta más que la duración de
        // la sesión, se recarga antes de que el usuario intente fichar con ella caducada.
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible' && Date.now() - paginaCargada > DURACION_SESION_MS) {
                window.location.reload();
            }
        });
    })();
</script>
