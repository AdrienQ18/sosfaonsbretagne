document.addEventListener('DOMContentLoaded', () => {
    const route = document.body.dataset.route;

    if (route === 'app_login') {
        initLoginRegisterPage();
    }

    initResetPasswordToggle();
    initResetPasswordMatch();
});

function initLoginRegisterPage() {
    const authContainer = document.getElementById('authContainer');

    if (!authContainer) {
        return;
    }

    const signUpButton = document.getElementById('signUp');
    const signInButton = document.getElementById('signIn');

    if (signUpButton && signInButton) {
        signUpButton.addEventListener('click', () => {
            authContainer.classList.add('right-panel-active');
        });

        signInButton.addEventListener('click', () => {
            authContainer.classList.remove('right-panel-active');
        });
    }

    initVolunteerRoles();
    initAvailabilityAllCheckbox();
    initLoginPasswordToggle();
    initRegisterPasswordStrength();
}

function initVolunteerRoles() {
    const benevoleCheckbox = document.querySelector('#registration_form_benevole');
    const rolesBlock = document.querySelector('#volunteer-roles');

    if (!benevoleCheckbox || !rolesBlock) {
        return;
    }

    function toggleRoles() {
        rolesBlock.style.display = benevoleCheckbox.checked ? 'block' : 'none';
    }

    benevoleCheckbox.addEventListener('change', toggleRoles);
    toggleRoles();
}

function initAvailabilityAllCheckbox() {
    const availabilityCheckboxes = document.querySelectorAll(
        '#registration_form_availabilitys input[type="checkbox"]'
    );

    if (!availabilityCheckboxes.length) {
        return;
    }

    const allCheckbox = Array.from(availabilityCheckboxes).find((checkbox) => {
        const label = document.querySelector(`label[for="${checkbox.id}"]`);

        return label?.textContent.trim().toLowerCase() === 'toutes';
    });

    if (!allCheckbox) {
        return;
    }

    allCheckbox.addEventListener('change', () => {
        availabilityCheckboxes.forEach((checkbox) => {
            if (checkbox !== allCheckbox) {
                checkbox.checked = allCheckbox.checked;
            }
        });
    });

    availabilityCheckboxes.forEach((checkbox) => {
        if (checkbox === allCheckbox) {
            return;
        }

        checkbox.addEventListener('change', () => {
            const otherCheckboxes = Array.from(availabilityCheckboxes)
                .filter((cb) => cb !== allCheckbox);

            allCheckbox.checked = otherCheckboxes.every((cb) => cb.checked);
        });
    });
}

function initLoginPasswordToggle() {
    const togglePasswordButtons = document.querySelectorAll('.login-toggle-password');

    if (!togglePasswordButtons.length) {
        return;
    }

    togglePasswordButtons.forEach((button) => {
        button.addEventListener('click', () => {
            togglePassword(button);
        });
    });
}

function initResetPasswordToggle() {
    const togglePasswordButtons = document.querySelectorAll('.reset-toggle-password');

    if (!togglePasswordButtons.length) {
        return;
    }

    togglePasswordButtons.forEach((button) => {
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

function initRegisterPasswordStrength() {
    const passwordInput = document.querySelector('.password-input');
    const passwordStrength = document.getElementById('password-strength');

    if (!passwordInput || !passwordStrength) {
        return;
    }

    passwordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        let score = 0;

        if (password.length >= 12) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/\d/.test(password)) score++;
        if (/[\W_]/.test(password)) score++;

        if (password.length === 0) {
            passwordStrength.textContent = '';
            passwordStrength.className = 'login-password-strength';
        } else if (score <= 2) {
            passwordStrength.textContent = 'Mot de passe faible';
            passwordStrength.className = 'login-password-strength weak';
        } else if (score <= 4) {
            passwordStrength.textContent = 'Mot de passe moyen';
            passwordStrength.className = 'login-password-strength medium';
        } else {
            passwordStrength.textContent = 'Mot de passe fort';
            passwordStrength.className = 'login-password-strength strong';
        }
    });
}

function initResetPasswordMatch() {
    const firstPassword = document.querySelector('[data-reset-password-first]');
    const secondPassword = document.querySelector('[data-reset-password-second]');
    const passwordMessage = document.querySelector('#password-match-message');

    if (!firstPassword || !secondPassword || !passwordMessage) {
        return;
    }

    function checkPasswordsMatch() {
        if (secondPassword.value.length === 0) {
            passwordMessage.textContent = '';
            passwordMessage.classList.remove('success', 'error');
            return;
        }

        if (firstPassword.value === secondPassword.value) {
            passwordMessage.textContent = 'Les mots de passe correspondent.';
            passwordMessage.classList.remove('error');
            passwordMessage.classList.add('success');
        } else {
            passwordMessage.textContent = 'Les mots de passe ne correspondent pas.';
            passwordMessage.classList.remove('success');
            passwordMessage.classList.add('error');
        }
    }

    firstPassword.addEventListener('input', checkPasswordsMatch);
    secondPassword.addEventListener('input', checkPasswordsMatch);
}
