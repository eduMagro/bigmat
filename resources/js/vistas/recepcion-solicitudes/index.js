document.getElementById('footer-global').classList.add('hidden');

window.recepcionSolicitudesApp = function recepcionSolicitudesApp(config) {
    return {
        routes: config.routes,
        recepcionadas: config.initialRecepcionadas || [],
        manualCode: '',
        manualCodeDraft: '',
        solicitudActual: null,
        currentScanCode: '',
        loadingSearch: false,
        loadingRecepcionar: false,
        statusMessage: 'Escanea o introduce un código QR para empezar.',
        showScannerModal: false,
        showManualModal: false,
        scannerError: '',
        scannerInstance: null,
        scannerBusy: false,
        expandedRecepcionadaId: null,
        activeSection: 'recepcionar',
        isLoadingRecepcionadas: false,

        init() {
            this.setupReveal();
        },

        setupReveal() {
            const cards = document.querySelectorAll('.reveal-on-scroll');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.12,
            });

            cards.forEach((card, idx) => {
                card.style.transitionDelay = `${Math.min(idx * 80, 240)}ms`;
                observer.observe(card);
            });
        },

        async switchSection(sectionKey) {
            this.activeSection = sectionKey;

            if (sectionKey === 'lista') {
                await this.refreshRecepcionadas(false);
            }
        },

        notify(icon, title) {
            if (!window.Swal || typeof window.Swal.fire !== 'function') {
                return;
            }

            window.Swal.fire({
                toast: true,
                position: 'top',
                icon,
                title,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                width: '22rem',
                padding: '0.7rem 0.9rem',
                backdrop: false,
            });
        },

        openManualModal() {
            this.manualCodeDraft = this.manualCode || '';
            this.showManualModal = true;
        },

        closeManualModal() {
            this.showManualModal = false;
        },

        async submitManualCode() {
            const code = (this.manualCodeDraft || '').trim();
            if (!code) {
                this.notify('warning', 'Introduce un código QR');
                return;
            }

            this.manualCode = code;
            this.closeManualModal();
            await this.buscarSolicitud();
        },

        async openScannerModal() {
            this.showScannerModal = true;
            this.scannerError = '';

            try {
                await this.ensureScannerLibrary();
                await this.startScanner();
            } catch (error) {
                this.scannerError = error?.message || 'No fue posible iniciar la cámara.';
                this.notify('error', this.scannerError);
            }
        },

        async closeScannerModal() {
            this.showScannerModal = false;
            await this.stopScanner();
        },

        ensureScannerLibrary() {
            if (window.Html5Qrcode) {
                return Promise.resolve();
            }

            return new Promise((resolve, reject) => {
                const existing = document.getElementById('html5-qrcode-lib');
                if (existing) {
                    existing.addEventListener('load', () => resolve(), { once: true });
                    existing.addEventListener('error', () => reject(new Error('No se pudo cargar la librería de escaneo.')), { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.id = 'html5-qrcode-lib';
                script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                script.async = true;
                script.onload = () => resolve();
                script.onerror = () => reject(new Error('No se pudo cargar la librería de escaneo.'));
                document.body.appendChild(script);
            });
        },

        async startScanner() {
            if (!window.Html5Qrcode) {
                throw new Error('Librería de escaneo no disponible.');
            }

            const isLocalhostHost = ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);
            if (!window.isSecureContext && !isLocalhostHost) {
                throw new Error('En móvil la cámara requiere HTTPS cuando accedes por IP. Usa https://192.168.0.107 o localhost.');
            }

            const cameras = await window.Html5Qrcode.getCameras();
            if (!cameras || cameras.length === 0) {
                throw new Error('No se detectaron cámaras disponibles.');
            }

            const preferredCamera = cameras.find((camera) => /back|rear|trasera|environment/i.test(camera.label || '')) || cameras[0];
            this.scannerInstance = new window.Html5Qrcode('qr-reader');

            await this.scannerInstance.start(
                preferredCamera.id,
                {
                    fps: 10,
                    qrbox: {
                        width: 250,
                        height: 250,
                    },
                    aspectRatio: 1,
                },
                async (decodedText) => {
                    if (this.scannerBusy) return;
                    this.scannerBusy = true;
                    this.manualCode = (decodedText || '').trim();
                    await this.buscarSolicitud();
                    await this.closeScannerModal();
                    setTimeout(() => {
                        this.scannerBusy = false;
                    }, 800);
                },
                () => { }
            );
        },

        async stopScanner() {
            if (!this.scannerInstance) return;

            try {
                await this.scannerInstance.stop();
            } catch (error) {
                // ignore stop errors when scanner is already closed
            }

            try {
                await this.scannerInstance.clear();
            } catch (error) {
                // ignore cleanup errors
            }

            this.scannerInstance = null;
        },

        async buscarSolicitud() {
            const code = (this.manualCode || '').trim();
            if (!code) {
                this.statusMessage = 'Introduce o escanea un código antes de buscar.';
                this.notify('warning', 'Introduce un código QR');
                return;
            }

            this.loadingSearch = true;
            this.statusMessage = 'Consultando solicitud en HPR...';

            try {
                const response = await fetch(this.routes.buscar, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        codigo: code,
                    }),
                });

                const json = await response.json();
                if (!response.ok || !json.success) {
                    throw new Error(json.message || json.error || 'No se encontró la solicitud.');
                }

                this.solicitudActual = json.data || null;
                this.currentScanCode = code;
                this.statusMessage = 'Solicitud cargada correctamente.';
                this.activeSection = 'recepcionar';
                this.notify('success', 'Solicitud encontrada');
            } catch (error) {
                this.solicitudActual = null;
                this.statusMessage = error.message || 'Error al consultar la solicitud.';
                this.notify('error', this.statusMessage);
            } finally {
                this.loadingSearch = false;
            }
        },

        async recepcionarSolicitud() {
            if (!this.solicitudActual) return;

            const codigo = this.solicitudActual.token_qr || this.currentScanCode || this.manualCode;
            if (!codigo) {
                this.statusMessage = 'No hay código válido para recepcionar.';
                this.notify('warning', this.statusMessage);
                return;
            }

            this.loadingRecepcionar = true;
            this.statusMessage = 'Registrando recepción en HPR...';

            try {
                const response = await fetch(this.routes.recepcionar, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        codigo,
                    }),
                });

                const json = await response.json();
                if (!response.ok || !json.success) {
                    throw new Error(json.message || json.error || 'No se pudo recepcionar la solicitud.');
                }

                this.statusMessage = json.mensaje || 'Solicitud recepcionada correctamente.';
                if (this.solicitudActual) {
                    this.solicitudActual.recepcionado = true;
                    this.solicitudActual.fecha_recepcion = json.data?.fecha_recepcion || null;
                    this.solicitudActual.recepcionado_por = json.data?.recepcionado_por || null;
                }

                await this.switchSection('lista');
                this.notify('success', this.statusMessage);
            } catch (error) {
                this.statusMessage = error.message || 'Error al recepcionar.';
                this.notify('error', this.statusMessage);
            } finally {
                this.loadingRecepcionar = false;
            }
        },

        async refreshRecepcionadas(showMessage = true) {
            this.isLoadingRecepcionadas = true;
            try {
                const response = await fetch(`${this.routes.recepcionadas}?limit=120`, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const json = await response.json();
                if (!response.ok || !json.success) {
                    throw new Error(json.message || json.error || 'No se pudo actualizar la lista.');
                }

                this.recepcionadas = Array.isArray(json.data) ? json.data : [];
                if (!this.recepcionadas.some((item) => item.id === this.expandedRecepcionadaId)) {
                    this.expandedRecepcionadaId = null;
                }

                if (showMessage) {
                    this.statusMessage = 'Listado de recepcionadas actualizado.';
                    this.notify('success', this.statusMessage);
                }
            } catch (error) {
                if (showMessage) {
                    this.statusMessage = error.message || 'No se pudo cargar el listado.';
                    this.notify('error', this.statusMessage);
                }
            } finally {
                this.isLoadingRecepcionadas = false;
            }
        },

        personaNombre(persona, fallback = '') {
            if (!persona) return fallback || 'Sin datos';
            if (typeof persona === 'string') return persona;

            return persona.nombre_completo || [persona.name, persona.primer_apellido, persona.segundo_apellido].filter(Boolean).join(' ') || fallback || 'Sin datos';
        },

        solicitanteNombreCorto(persona) {
            if (!persona) return 'Sin datos';
            if (typeof persona === 'string') {
                const parts = persona.trim().split(/\s+/).filter(Boolean);
                return parts.slice(0, 2).join(' ') || persona;
            }

            const corto = [persona.name, persona.primer_apellido].filter(Boolean).join(' ').trim();
            if (corto !== '') {
                return corto;
            }

            return this.personaNombre(persona);
        },

        toggleRecepcionadaDetalle(id) {
            this.expandedRecepcionadaId = this.expandedRecepcionadaId === id ? null : id;
        },

        formatDate(value) {
            if (!value) return 'N/D';

            const d = new Date(value.replace(' ', 'T'));
            if (Number.isNaN(d.getTime())) return value;

            return d.toLocaleString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    };
};
