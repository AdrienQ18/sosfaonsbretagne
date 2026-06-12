export function initPasswordToggle(selector) {
    document.querySelectorAll(selector).forEach((button) => {
        button.addEventListener('click', () => {
            togglePassword(button);
        });
    });
}

function togglePassword(button) {
    const targetId = button.dataset.target;
    const eyeOpen = button.dataset.eyeOpen;
    const eyeClose = button.dataset.eyeClose;

    const input = document.getElementById(targetId);
    const image = button.querySelector('img');

    if (!input || !image || !eyeOpen || !eyeClose) {
        return;
    }

    const isHidden = input.type === 'password';

    input.type = isHidden ? 'text' : 'password';
    image.src = isHidden ? eyeClose : eyeOpen;

    button.setAttribute(
        'aria-label',
        isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe'
    );

    button.setAttribute(
        'aria-pressed',
        isHidden ? 'true' : 'false'
    );
}
