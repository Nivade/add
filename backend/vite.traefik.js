import { loadEnv } from 'vite';

/**
 * Vite server config matching the Traefik routers in compose.yaml.
 *
 * import { traefikServer } from './vite.traefik.js';
 * export default defineConfig(({ mode }) => ({ plugins: [...], server: traefikServer(mode) }));
 */
export function traefikServer(mode) {
    // Read from .env, not process.env: compose.yaml passes only VITE_PORT into the container.
    const env = loadEnv(mode, process.cwd(), '');
    const host = `vite.${env.APP_SLUG}.${env.BASE_DOMAIN}`;
    const port = Number(env.VITE_PORT) || 5173;

    return {
        host: '0.0.0.0',
        port,
        cors: true,
        // Traefik terminates TLS at vite.<host> and forwards plain http here; an
        // origin of localhost makes every asset mixed content and kills the HMR socket.
        origin: `https://${host}`,
        hmr: {
            host,
            clientPort: 443,
            protocol: 'wss',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    };
}
