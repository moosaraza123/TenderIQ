import { useEffect, useState } from 'react';
import { urgencyLevel, timeUntil } from '@/lib/formatters';

export function useCountdown(date) {
    const [display, setDisplay] = useState(() => timeUntil(date));
    const [level, setLevel]     = useState(() => urgencyLevel(date));

    useEffect(() => {
        if (!date) return;

        const tick = () => {
            setDisplay(timeUntil(date));
            setLevel(urgencyLevel(date));
        };

        const id = setInterval(tick, 60_000);
        return () => clearInterval(id);
    }, [date]);

    return { display, level };
}
