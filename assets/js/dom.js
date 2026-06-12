export function onReady(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, {once: true});
        return;
    }

    callback();
}

export function isRoute(routeName) {
    return document.body?.dataset.route === routeName;
}
