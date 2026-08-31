import './bootstrap';
import { registerSW } from 'virtual:pwa-register';
import Chart from 'chart.js/auto';

async function clearServiceWorkerState() {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    const registrations = await navigator.serviceWorker.getRegistrations();
    await Promise.all(registrations.map((registration) => registration.unregister()));

    if ('caches' in window) {
        const keys = await caches.keys();
        await Promise.all(keys.map((key) => caches.delete(key)));
    }
}

registerSW({
    immediate: true,
    onRegisteredSW(_swUrl, registration) {
        if (!registration) {
            return;
        }

        // Pick up new SW after deploys even if the tab stays open.
        registration.update().catch(() => {});

        registration.addEventListener('updatefound', () => {
            const installing = registration.installing;
            if (!installing) {
                return;
            }

            installing.addEventListener('statechange', () => {
                // Install failed (commonly bad-precaching-response 404 on old hashed assets).
                if (installing.state === 'redundant') {
                    clearServiceWorkerState().catch(() => {});
                }
            });
        });
    },
    onRegisterError() {
        clearServiceWorkerState().catch(() => {});
    },
});

import * as RaffleWheel from './raffle-wheel.js';
import './tiptap-editor.js';

// Make Chart available globally for Alpine.js components
window.Chart = Chart;

// Make RaffleWheel functions available globally
window.RaffleWheel = RaffleWheel;
