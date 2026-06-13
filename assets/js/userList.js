import {isRoute, onReady} from './dom.js';
import {initAutoSubmitFilterForm} from './filterForm.js';

onReady(() => {
    if (!isRoute('admin_userList')) {
        return;
    }

    initAutoSubmitFilterForm({
        formSelector: '#user-filter-form',
        resetSelector: '#reset-user-filters',
    });
});
