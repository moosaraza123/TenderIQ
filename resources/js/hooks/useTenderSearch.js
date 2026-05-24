import { useCallback, useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';

export function useTenderSearch(initialFilters = {}) {
    const [filters, setFilters] = useState({
        keyword:      '',
        country:      '',
        category:     '',
        sector:       '',
        city:         '',
        status:       '',
        tender_type:  '',
        closing_from: '',
        closing_to:   '',
        sort:         'closing_soon',
        ...initialFilters,
    });

    const debounceRef = useRef(null);

    const applyFilters = useCallback((newFilters) => {
        const clean = Object.fromEntries(
            Object.entries(newFilters).filter(([, v]) => v !== '' && v !== null && v !== undefined)
        );
        router.get('/tenders', clean, { preserveState: true, replace: true });
    }, []);

    const setFilter = useCallback((key, value) => {
        setFilters(prev => {
            const updated = { ...prev, [key]: value };

            if (key === 'keyword') {
                if (debounceRef.current) clearTimeout(debounceRef.current);
                debounceRef.current = setTimeout(() => applyFilters(updated), 300);
            } else {
                applyFilters(updated);
            }

            return updated;
        });
    }, [applyFilters]);

    const reset = useCallback(() => {
        const empty = { keyword: '', country: '', category: '', sector: '', city: '', status: '', tender_type: '', closing_from: '', closing_to: '', sort: 'closing_soon' };
        setFilters(empty);
        router.get('/tenders', {}, { preserveState: false });
    }, []);

    useEffect(() => () => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
    }, []);

    const activeCount = Object.entries(filters).filter(([k, v]) => {
        if (k === 'sort') return false;
        return v !== '' && v !== null && v !== undefined;
    }).length;

    return { filters, setFilter, reset, activeCount };
}
