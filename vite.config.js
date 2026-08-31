import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';

const __dirname = dirname(fileURLToPath(import.meta.url));

/** Laravel Vite uses publicDir: false; PWA still needs stable precache revisions for files in public/. */
function publicAssetRevision(filename) {
    return createHash('md5').update(readFileSync(resolve(__dirname, 'public', filename))).digest('hex');
}

const ngrokDomain = process.env.VITE_HMR_HOST || process.env.APP_URL?.replace(/^https?:\/\//, '').replace(/\/$/, '');
const isNgrok = ngrokDomain?.includes('ngrok');

// Plugin to suppress @property CSS warnings
const suppressCssWarnings = () => {
    return {
        name: 'suppress-css-warnings',
        buildStart() {
            // Suppress console warnings about @property during build
            const originalWarn = console.warn;
            console.warn = (...args) => {
                const message = args.join(' ');
                if (message.includes('@property') || message.includes('Unknown at rule') || message.includes('--radialprogress')) {
                    return;
                }
                originalWarn.apply(console, args);
            };
        },
    };
};

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
            detectTls: isNgrok ? ngrokDomain : undefined,
        }),
        VitePWA({
            // Emit SW at site root so scope "/" is valid (Laravel uses base "/build/" for assets).
            filename: '../sw.js',
            scope: '/',
            registerType: 'autoUpdate',
            injectRegister: false,
            includeManifestIcons: false,
            manifest: {
                name: 'Hope Village',
                short_name: 'Hope Village',
                description:
                    'A community hub for migrants community in Singapore — brought to you by Hope Initiative Alliance in partnership with Advancer IFM and other supporting partners.',
                theme_color: '#facc15',
                background_color: '#e5e7eb',
                display: 'standalone',
                start_url: '/',
                scope: '/',
                icons: [
                    {
                        src: '/hv-logo.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/hv-logo.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/hv-logo.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
            },
            workbox: {
                // Avoid precaching hashed Vite JS/CSS — filenames change every build and cause
                // bad-precaching-response 404s when an old SW installs against a new deploy.
                globPatterns: ['**/*.{webmanifest,ico,png,svg}'],
                // SW is emitted at /sw.js; keep any matched build assets under /build/.
                modifyURLPrefix: {
                    'assets/': 'build/assets/',
                },
                additionalManifestEntries: [
                    { url: '/hv-logo.png', revision: publicAssetRevision('hv-logo.png') },
                ],
                // Plugin default is "index.html" (SPA). Laravel is server-rendered; that breaks navigations.
                // Falsy value omits NavigationRoute in generated sw.js (workbox sw-template).
                navigateFallback: null,
                cleanupOutdatedCaches: true,
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.(googleapis|gstatic)\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'google-fonts-cache',
                            expiration: {
                                maxEntries: 20,
                                maxAgeSeconds: 60 * 60 * 24 * 365,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                ],
                skipWaiting: true,
                clientsClaim: true,
            },
            devOptions: {
                enabled: false,
            },
        }),
        suppressCssWarnings(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/chart.js')) return 'chart';
                    if (id.includes('node_modules/@tiptap')) return 'tiptap';
                },
            },
            onwarn(warning, warn) {
                // Suppress @property warnings from DaisyUI
                if (warning.message?.includes('@property') || warning.message?.includes('Unknown at rule')) {
                    return;
                }
                warn(warning);
            },
        },
        chunkSizeWarningLimit: 600,
        // Use esbuild for CSS minification to avoid @property warnings
        cssMinify: 'esbuild',
    },
    css: {
        devSourcemap: true,
    },
    server: {
        host: '0.0.0.0', // Allow external connections
        port: 5173,
        origin: isNgrok ? `https://${ngrokDomain}` : undefined,
        hmr: {
            host: ngrokDomain || 'localhost',
            protocol: isNgrok ? 'wss' : 'ws',
            clientPort: isNgrok ? 443 : 5173,
        },
        strictPort: false,
    },
});