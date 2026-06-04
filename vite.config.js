import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/schedule-dispatch.css',
                'resources/js/app.js',
                'resources/js/schedule-dispatch.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        port: 5176,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
