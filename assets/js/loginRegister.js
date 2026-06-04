document.addEventListener('DOMContentLoaded', () => {
    const authContainer = document.getElementById('authContainer');

    if (!authContainer) {
        return;
    }
    if (document.body.dataset.route !== 'app_login') {
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

    const benevoleCheckbox = document.querySelector('#registration_form_benevole');
    const rolesBlock = document.querySelector('#volunteer-roles');

    function toggleRoles() {
        if (benevoleCheckbox && rolesBlock) {
            rolesBlock.style.display = benevoleCheckbox.checked ? 'block' : 'none';
        }
    }

    if (benevoleCheckbox && rolesBlock) {
        benevoleCheckbox.addEventListener('change', toggleRoles);
        toggleRoles();
    }

    const availabilityCheckboxes = document.querySelectorAll(
        '#registration_form_availabilitys input[type="checkbox"]'
    );

    const allCheckbox = Array.from(availabilityCheckboxes).find((checkbox) => {
        const label = document.querySelector(`label[for="${checkbox.id}"]`);
        return label?.textContent.trim().toLowerCase() === 'toutes';
    });

    if (allCheckbox) {
        allCheckbox.addEventListener('change', () => {
            availabilityCheckboxes.forEach((checkbox) => {
                if (checkbox !== allCheckbox) {
                    checkbox.checked = allCheckbox.checked;
                }
            });
        });

        availabilityCheckboxes.forEach((checkbox) => {
            if (checkbox !== allCheckbox) {
                checkbox.addEventListener('change', () => {
                    const otherCheckboxes = Array.from(availabilityCheckboxes)
                        .filter((cb) => cb !== allCheckbox);

                    allCheckbox.checked = otherCheckboxes.every((cb) => cb.checked);
                });
            }
        });
    }

    const togglePasswordButtons = document.querySelectorAll('.toggle-password');

    togglePasswordButtons.forEach((button) => {
        button.addEventListener('click', () => {
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
        });
    });

    const passwordInput = document.querySelector('.password-input');
    const passwordStrength = document.getElementById('password-strength');

    if (passwordInput && passwordStrength) {
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
                passwordStrength.className = 'password-strength';
            } else if (score <= 2) {
                passwordStrength.textContent = 'Mot de passe faible';
                passwordStrength.className = 'password-strength weak';
            } else if (score <= 4) {
                passwordStrength.textContent = 'Mot de passe moyen';
                passwordStrength.className = 'password-strength medium';
            } else {
                passwordStrength.textContent = 'Mot de passe fort';
                passwordStrength.className = 'password-strength strong';
            }
        });
    }
});
