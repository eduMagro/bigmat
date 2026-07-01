import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig(({ command, mode }) => {
    const env = loadEnv(mode, process.cwd(), "");

    const serverHost = env.VITE_DEV_SERVER_HOST || "0.0.0.0";
    const envServerPort = env.VITE_DEV_SERVER_PORT || env.VITE_PORT;
    const serverPort = Number(envServerPort) || 5173;
    const strictPort = String(env.VITE_STRICT_PORT || "").toLowerCase() === "true";

    const hmrHost = env.VITE_HMR_HOST || serverHost || "localhost";
    const hmrPort =
        strictPort && env.VITE_HMR_PORT
            ? Number(env.VITE_HMR_PORT)
            : undefined;

    return {
        plugins: [
            laravel({
                input: [
                    "resources/css/app.css",
                    "resources/js/app.js",
                    "resources/js/vistas/contactos/mobile.js",
                    "resources/js/vistas/recepcion-solicitudes/index.js",
                    "resources/js/vistas/vacaciones/index.js",
                ],
                refresh: true,
            }),
        ],
        build: {
            rollupOptions: {
                output: {
                    entryFileNames: "assets/[name].[hash].js",
                    chunkFileNames: "assets/[name].[hash].js",
                    assetFileNames: "assets/[name].[hash].[ext]",
                },
            },
        },
        server: {
            host: serverHost,
            port: serverPort,
            strictPort,
            hmr: {
                host: hmrHost,
                ...(hmrPort ? { port: hmrPort } : {}),
            },
        },
    };
});
