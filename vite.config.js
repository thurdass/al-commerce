import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            hotFile: process.env.LARAVEL_VITE_HOT_FILE ?? 'public/hot',
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        ...(process.env.DOCKER_VITE_HOST ? {
            hmr: {
                host: process.env.DOCKER_VITE_HOST,
                clientPort: Number(process.env.DOCKER_VITE_PORT ?? 5175),
            },
        } : {}),
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
