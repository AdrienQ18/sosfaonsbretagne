import {isRoute, onReady} from './dom.js';
import {initSelectAllCheckboxGroup} from './checkboxGroup.js';

onReady(() => {
    if (!isRoute('user_profile_edit')) {
        return;
    }

    initSelectAllCheckboxGroup('.availability-options input[type="checkbox"]');
});
