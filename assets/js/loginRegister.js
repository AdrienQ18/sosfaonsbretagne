import {isRoute, onReady} from './dom.js';
import {initSelectAllCheckboxGroup} from './checkboxGroup.js';
import {initPasswordToggle} from './passwordToggle.js';

onReady(() => {
    if (isRoute('app_login')) {
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
    initSelectAllCheckboxGroup('#registration_form_availabilitys input[type="checkbox"]');
}

function initLoginPasswordToggle() {
    initPasswordToggle('.login-toggle-password');
}

function initResetPasswordToggle() {
    initPasswordToggle('.reset-toggle-password');
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
