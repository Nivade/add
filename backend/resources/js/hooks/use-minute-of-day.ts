import { usePage } from '@inertiajs/react';
import { createContext, useContext, useEffect, useState } from 'react';

/** The server said what minute it is in their zone; this only counts forward from it. */
export function useMinuteOfDay(from: number, live = true): number {
    const [minute, setMinute] = useState(from);

    useEffect(() => {
        setMinute(from);

        if (!live) {
            return;
        }

        const startedAt = Date.now();
        const tick = setInterval(
            () =>
                setMinute(from + Math.floor((Date.now() - startedAt) / 60_000)),
            10_000,
        );

        return () => clearInterval(tick);
    }, [from, live]);

    return minute;
}

/** The layout's one clock, so a page saying "done around" agrees with the day strip beside it. */
export const NowMinuteContext = createContext<number | null>(null);

/** Outside a clocked layout the server's minute stands still, which a calm screen can afford. */
export function useNowMinute(): number {
    const { rail } = usePage().props;
    const live = useContext(NowMinuteContext);

    if (live !== null) {
        return live;
    }

    const now = new Date();

    return rail?.nowMinute ?? now.getHours() * 60 + now.getMinutes();
}
