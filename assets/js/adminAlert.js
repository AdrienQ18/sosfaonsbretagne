import {isRoute, onReady} from './dom.js';
import {initAutoSubmitFilterForm} from './filterForm.js';

onReady(() => {
    if (!isRoute('admin_alert')) {
        return;
    }

    initAutoSubmitFilterForm({
        formSelector: '#alert-filter-form',
        resetSelector: '#reset-alert-filters',
        fallbackResetUrl: '/admin/alert',
    });
});
