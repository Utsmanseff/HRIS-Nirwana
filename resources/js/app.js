// Nirwana HRIS — app entry.

// Register the service worker (PWA shell + web push).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((err) => {
            console.warn('SW registration failed:', err);
        });
    });
}
