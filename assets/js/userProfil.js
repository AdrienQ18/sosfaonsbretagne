document.addEventListener('DOMContentLoaded', () => {
    const route = document.body.dataset.route;

    if (route !== 'user_profile_edit') {
        return;
    }

    initAvailabilityAllCheckbox();
});

function initAvailabilityAllCheckbox() {
    const availabilityCheckboxes = document.querySelectorAll(
        '.availability-options input[type="checkbox"]'
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
                .filter((checkbox) => checkbox !== allCheckbox);

            allCheckbox.checked = otherCheckboxes.every((checkbox) => checkbox.checked);
        });
    });
}
