function findCheckboxByLabel(checkboxes, matcher) {
    return Array.from(checkboxes).find((checkbox) => {
        const label = document.querySelector(`label[for="${checkbox.id}"]`);
        const labelText = label?.textContent.trim().toLowerCase() || '';

        return matcher(labelText, checkbox);
    });
}

export function initSelectAllCheckboxGroup(selector, labelText = 'toutes') {
    const checkboxes = document.querySelectorAll(selector);

    if (!checkboxes.length) {
        return;
    }

    const allCheckbox = findCheckboxByLabel(
        checkboxes,
        (text) => text === labelText.toLowerCase()
    );

    if (!allCheckbox) {
        return;
    }

    const otherCheckboxes = Array.from(checkboxes)
        .filter((checkbox) => checkbox !== allCheckbox);

    allCheckbox.addEventListener('change', () => {
        otherCheckboxes.forEach((checkbox) => {
            checkbox.checked = allCheckbox.checked;
        });
    });

    otherCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            allCheckbox.checked = otherCheckboxes.every((item) => item.checked);
        });
    });
}

export function findCheckboxByLabelText(selector, matcher) {
    return findCheckboxByLabel(document.querySelectorAll(selector), matcher);
}
