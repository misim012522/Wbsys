import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const viteHost = env.VITE_DEV_SERVER_HOST || 'localhost';
    const tenantBaseDomain = env.LOCAL_TENANT_BASE_DOMAIN || 'lvh.me';
    const assetBaseUrl = env.ASSET_BASE_URL;

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
        base: assetBaseUrl || '/',
        server: {
            host: viteHost,
            strictPort: true,
            origin: assetBaseUrl || `http://${viteHost}:5173`,
            cors: {
                origin: [
                    /^https?:\/\/(?:[^.]+\.)?lvh\.me(?::\d+)?$/,
                    /^https?:\/\/(?:localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/,
                    new RegExp(`^https?:\\/\\/(?:[^.]+\\.)?${tenantBaseDomain.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?::\\d+)?$`),
                    /^https?:\/\/(?:[^.]+\.)?ngrok-free\.dev$/,
                    /^https?:\/\/(?:[^.]+\.)?ngrok\.io$/,
                ],
            },
            hmr: {
                host: viteHost,
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
