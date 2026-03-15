if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW deployed:', registration.scope);
            })
            .catch(error => {
                console.log('SW failed:', error);
            });
    });
}
