document.addEventListener('DOMContentLoaded', () => {
    const route = document.body.dataset.route;

    if (route !== 'admin_modify') {
        return;
    }

    initAvailabilityAllCheckbox();
    initAdminRoleConfirmation();
    initActiveAccountConfirmation();
});

function initAvailabilityAllCheckbox() {
    const availabilityCheckboxes = document.querySelectorAll(
        '#user_Availabilitys input[type="checkbox"], #user_availabilitys input[type="checkbox"]'
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

function initAdminRoleConfirmation() {
    const roleCheckboxes = document.querySelectorAll(
        '#user_roles input[type="checkbox"]'
    );

    if (!roleCheckboxes.length) {
        return;
    }

    const adminCheckbox = Array.from(roleCheckboxes).find((checkbox) => {
        const label = document.querySelector(`label[for="${checkbox.id}"]`);

        return label?.textContent.trim().toLowerCase().includes('administrateur')
            || checkbox.value === 'ROLE_ADMIN';
    });

    if (!adminCheckbox) {
        return;
    }

    let previousState = adminCheckbox.checked;

    adminCheckbox.addEventListener('change', () => {
        const message = adminCheckbox.checked
            ? 'Attention : vous êtes sur le point d’attribuer les droits administrateur à cet utilisateur. Confirmer ?'
            : 'Attention : vous êtes sur le point de retirer les droits administrateur à cet utilisateur. Confirmer ?';

        const confirmed = confirm(message);

        if (!confirmed) {
            adminCheckbox.checked = previousState;
            return;
        }

        previousState = adminCheckbox.checked;
    });
}

function initActiveAccountConfirmation() {
    const activeCheckbox = document.querySelector(
        '#user_actif'
    );

    if (!activeCheckbox) {
        return;
    }

    let previousState = activeCheckbox.checked;

    activeCheckbox.addEventListener('change', () => {
        const message = activeCheckbox.checked
            ? 'Voulez-vous vraiment activer ce compte utilisateur ?'
            : 'Voulez-vous vraiment désactiver ce compte utilisateur ?';

        const confirmed = confirm(message);

        if (!confirmed) {
            activeCheckbox.checked = previousState;
            return;
        }

        previousState = activeCheckbox.checked;
    });
}
