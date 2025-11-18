document.addEventListener('DOMContentLoaded', function () {
    var container = document.querySelector('[data-role="user-card"]');

    if (!container) {
        return;
    }

    container.classList.add('my-user-card--initialized');
});

