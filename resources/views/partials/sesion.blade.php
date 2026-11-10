{{--
    Sesión caducada en peticiones fetch.

    El login siempre es "recordado": cuando la sesión caduca, las consultas (GET) siguen
    funcionando porque el usuario se re-autentica solo, pero el token CSRF de la página ya
    no vale y cualquier envío (POST) devuelve un 419 en HTML. Aquí se intercepta ese 419,
    se pide un token nuevo y se repite la petición sin que el usuario note nada. Si no se
    puede renovar (sesión realmente cerrada), la página se recarga sola.
--}}
<script>
    (function() {
        if (window.__sesionFetchInstalado) return; // el <head> persiste con wire:navigate
        window.__sesionFetchInstalado = true;

        const URL_TOKEN = @json(route('csrf.token'));
        const fetchOriginal = window.fetch.bind(window);
        let renovando = null;

        window.recargarPorSesionCaducada = function() {
            if (window.__recargandoPorSesion) return;
            window.__recargandoPorSesion = true;
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

        function renovarToken() {
            if (!renovando) {
                renovando = fetchOriginal(URL_TOKEN, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        cache: 'no-store',
                    })
                    .then(r => (r.ok ? r.json() : null))
                    .then(d => {
                        const token = (d && d.token) || null;
                        if (token) {
                            document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
                            document.querySelectorAll('input[name="_token"]').forEach(i => { i.value = token; });
                        }
                        return token;
                    })
                    .catch(() => null)
                    .finally(() => { setTimeout(() => { renovando = null; }, 0); });
            }
            return renovando;
        }

        function esMismoOrigen(url) {
            try {
                return new URL(url, window.location.href).origin === window.location.origin;
            } catch (e) {
                return false;
            }
        }

        window.fetch = async function(input, init) {
            const respuesta = await fetchOriginal(input, init);
            if (respuesta.status !== 419) return respuesta;

            // Solo se reintenta lo que se puede reenviar con seguridad
            if (typeof input !== 'string' && !(input instanceof URL)) return respuesta;
            if (!esMismoOrigen(String(input))) return respuesta;

            const headers = new Headers((init && init.headers) || {});
            if (headers.has('X-Livewire')) return respuesta; // Livewire gestiona su propia caducidad

            const token = await renovarToken();
            if (!token) {
                window.recargarPorSesionCaducada();
                return new Promise(() => {}); // la página se recarga: no se continúa
            }

            headers.set('X-CSRF-TOKEN', token);
            let body = init ? init.body : undefined;
            if (body instanceof FormData && body.has('_token')) {
                body.set('_token', token);
            } else if (typeof body === 'string' && body.includes('"_token"')) {
                try {
                    const datos = JSON.parse(body);
                    if (datos && typeof datos === 'object' && '_token' in datos) {
                        datos._token = token;
                        body = JSON.stringify(datos);
                    }
                } catch (e) { /* no era JSON: se reenvía tal cual */ }
            }

            const reintento = await fetchOriginal(input, { ...(init || {}), headers, body });
            if (reintento.status === 419) {
                window.recargarPorSesionCaducada();
                return new Promise(() => {});
            }
            return reintento;
        };

        // Devuelve los datos JSON de una respuesta. Si la sesión está cerrada recarga la
        // página; si llega algo que no es JSON lanza un error legible para el usuario.
        window.leerRespuestaJson = function(r) {
            if (r.status === 419 || r.status === 401 || r.redirected) {
                window.recargarPorSesionCaducada();
                return new Promise(() => {});
            }
            const esJson = (r.headers.get('content-type') || '').includes('application/json');
            if (!esJson) {
                throw new Error(`El servidor ha devuelto un error (código ${r.status}). Vuelve a intentarlo en unos minutos.`);
            }
            return r.json();
        };
        window.leerRespuestaFichaje = window.leerRespuestaJson;

        window.mensajeErrorFichaje = function(err) {
            if (err instanceof TypeError) {
                return 'No hay conexión con el servidor. Comprueba la cobertura o el wifi y vuelve a intentarlo.';
            }
            return (err && err.message) || 'No se pudo comunicar con el servidor';
        };
    })();
</script>
