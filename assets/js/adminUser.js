import {isRoute, onReady} from './dom.js';
import {findCheckboxByLabelText, initSelectAllCheckboxGroup} from './checkboxGroup.js';

onReady(() => {
    if (!isRoute('admin_modify')) {
        return;
    }

    initAvailabilityAllCheckbox();
    initAdminRoleConfirmation();
    initActiveAccountConfirmation();
});

function initAvailabilityAllCheckbox() {
    initSelectAllCheckboxGroup(
        '#user_Availabilitys input[type="checkbox"], #user_availabilitys input[type="checkbox"]'
    );
}

function initAdminRoleConfirmation() {
    const adminCheckbox = findCheckboxByLabelText(
        '#user_roles input[type="checkbox"]',
        (label, checkbox) => label.includes('administrateur') || checkbox.value === 'ROLE_ADMIN'
    );

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
