import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.jsx', 'resources/js/landing-app.jsx'],
            refresh: true,
        }),
        react(),
    ],
    server: {
        cors: true,
        host: process.env.NODE_ENV === 'production' ? '0.0.0.0' : '127.0.0.1',
        port: 5173,
        hmr: {
            host: process.env.NODE_ENV === 'production' ? '0.0.0.0' : '127.0.0.1',
        },
    },
    build: {
        manifest: true,
        outDir: 'public/build',
        rollupOptions: {
            input: ['resources/js/app.jsx', 'resources/js/landing-app.jsx'],
        },
    },
});
