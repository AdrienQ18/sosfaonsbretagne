import {isRoute, onReady} from './dom.js';
import {initAutoSubmitFilterForm} from './filterForm.js';

onReady(() => {
    if (!isRoute('admin_pre_order')) {
        return;
    }

    initAutoSubmitFilterForm({
        formSelector: '#pre-order-filter-form',
        resetSelector: '#reset-preorder-filters',
        fallbackResetUrl: '/admin/preorder',
    });
});
