import { router } from '@inertiajs/react';

export function applyFilters(baseUrl, filters) {
    const clean = Object.fromEntries(
        Object.entries(filters).filter(([, v]) => v !== '' && v !== null && v !== undefined)
    );
    router.get(baseUrl, clean, { preserveState: true, replace: true });
}

export function goToTender(tenderNumber) {
    router.get(`/tenders/${tenderNumber}`);
}
