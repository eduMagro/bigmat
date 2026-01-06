<a id="notificacion-alerta" href="{{ route('alertas.index') }}" wire:navigate>
    <p id="notificacion-alertas-texto">🔔 Tienes mensajes sin leer</p>
</a>

<style>
    /* 🔴 Estilo base de la notificación */
    #notificacion-alerta {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: rgba(220, 38, 38, 0.8);
        /* Rojo translúcido */
        color: white;
        padding: 12px 20px;
        font-size: 16px;
        font-weight: bold;
        border-radius: 8px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        opacity: 0;
        /* Inicialmente oculto */
        transform: translateY(-10px);
        display: none;
        z-index: 1000;
        text-decoration: none;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    /* 🎭 Efecto hover para dar feedback */
    #notificacion-alerta:hover {
        background-color: rgba(220, 38, 38, 1);
        /* Más opaco al pasar el mouse */
        transform: scale(1.05);
        /* Pequeño zoom */
    }

    /* 🔄 Animación de aparición */
    @keyframes fadeIn {
        0% {
            opacity: 0;
            transform: translateY(-10px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* 🔁 Parpadeo sutil cada 3 segundos */
    @keyframes blink {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.85;
        }
    }

    /* Aplicar parpadeo después de la aparición */
    #notificacion-alerta.visible {
        animation: blink 3s infinite ease-in-out;
    }
</style>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        fetch("/alertas/sin-leer", {
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.cantidad > 0) {
                    let notificacion = document.getElementById("notificacion-alerta");
                    let notificacionTexto = document.getElementById("notificacion-alertas-texto");

                    let mensaje = data.cantidad === 1 ?
                        `🔔 Tienes 1 mensaje sin leer` :
                        `🔔 Tienes ${data.cantidad} mensajes sin leer`;

                    notificacion.style.display = "block";
                    notificacion.classList.add("visible");
                    notificacionTexto.innerHTML = mensaje;

                    // if ("{{ auth()->user()->categoria }}" === "gruista") {
                    //     let sonido = new Audio("/sonidos/alerta1.mp3");
                    //     sonido.play().catch(error => console.error("Error al reproducir el sonido:", error));
                    // }
                }
            })
            .catch(error => console.error("Error al obtener alertas:", error));
    });
</script>
