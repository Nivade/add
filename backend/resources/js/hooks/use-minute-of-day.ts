import { useEffect, useState } from 'react';

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
