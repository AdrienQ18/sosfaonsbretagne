import {isRoute, onReady} from './dom.js';
import {initAutoSubmitFilterForm} from './filterForm.js';

onReady(() => {
    if (isRoute('donation')) {
        initDonationPage();
    }

    if (isRoute('admin_donation')) {
        initAdminDonationPage();
    }
});

function initDonationPage() {
    const stepAmount = document.querySelector('#step-amount');
    const stepInfos = document.querySelector('#step-infos');
    const amountInput = document.querySelector('[data-don-amount-input]');
    const submitBtn = document.querySelector('#submit-donation');
    const goToInfosBtn = document.querySelector('#go-to-infos');
    const taxInfo = document.querySelector('#tax-info');
    const donorTitle = document.querySelector('#donor-title');
    const addressTitle = document.querySelector('#address-title');
    const companyFields = document.querySelector('#company-fields');
    const amountButtons = document.querySelectorAll('.don-amount-btn');
    const donorTypeInputs = document.querySelectorAll('input[name="donation[donorType]"]');

    const companyInputs = [
        document.querySelector('#donation_form_companyName'),
        document.querySelector('#donation_form_companySiret'),
    ];

    if (!stepAmount || !stepInfos || !amountInput || !goToInfosBtn) {
        return;
    }

    amountButtons.forEach((button) => {
        button.addEventListener('click', () => {
            amountButtons.forEach((btn) => {
                btn.classList.remove('is-active');
                btn.setAttribute('aria-pressed', 'false');
            });

            button.classList.add('is-active');
            button.setAttribute('aria-pressed', 'true');

            amountInput.value = button.dataset.amount;

            if (submitBtn) {
                submitBtn.textContent = `Donner ${button.dataset.amount} €`;
            }
        });
    });

    amountInput.addEventListener('input', () => {
        amountButtons.forEach((button) => {
            button.classList.remove('is-active');
            button.setAttribute('aria-pressed', 'false');
        });

        if (submitBtn && amountInput.value && Number(amountInput.value) > 0) {
            submitBtn.textContent = `Donner ${amountInput.value} €`;
        }
    });

    goToInfosBtn.addEventListener('click', () => {
        if (!amountInput.value || Number(amountInput.value) <= 0) {
            alert('Veuillez choisir ou saisir un montant.');
            return;
        }

        stepAmount.style.display = 'none';
        stepInfos.style.display = 'block';

        if (submitBtn) {
            submitBtn.textContent = `Donner ${amountInput.value} €`;
        }
    });

    function setRequired(inputs, required) {
        inputs.forEach((input) => {
            if (input) {
                input.required = required;
            }
        });
    }

    function toggleDonorFields() {
        const selectedType = document.querySelector(
            'input[name="donation[donorType]"]:checked'
        );

        if (!selectedType || !donorTitle || !addressTitle || !companyFields || !taxInfo) {
            return;
        }

        const addressHeading = addressTitle.querySelector('h3');

        if (selectedType.value === 'entreprise') {
            donorTitle.textContent = 'Entreprise donatrice';

            if (addressHeading) {
                addressHeading.textContent = 'Adresse de l’entreprise';
            }

            companyFields.style.display = 'block';
            setRequired(companyInputs, true);

            taxInfo.textContent =
                'Un reçu fiscal vous sera envoyé par e-mail après la confirmation de votre don. 60 % du montant du don est déductible de l’impôt sur les sociétés.';
        } else {
            donorTitle.textContent = 'Particulier donneur';

            if (addressHeading) {
                addressHeading.textContent = 'Adresse';
            }

            companyFields.style.display = 'none';
            setRequired(companyInputs, false);

            taxInfo.textContent =
                'Un reçu fiscal vous sera envoyé par e-mail après la confirmation de votre don. 66 % du montant de votre don est déductible des impôts.';
        }
    }

    donorTypeInputs.forEach((input) => {
        input.addEventListener('change', toggleDonorFields);
    });

    toggleDonorFields();
}

function initAdminDonationPage() {
    initAutoSubmitFilterForm({
        formSelector: '#donation-filter-form',
        resetSelector: '#reset-filters',
        fallbackResetUrl: '/admin/donation',
    });
}
