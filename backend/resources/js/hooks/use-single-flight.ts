import { useRef } from 'react';

/** Spread onto a `<Form>` to drop a second submit while the first is in flight; `disabled` would drop keyboard focus mid-request. */
export function useSingleFlight() {
    const inFlight = useRef(false);

    return {
        onBefore: () => !inFlight.current,
        onStart: () => {
            inFlight.current = true;
        },
        onFinish: () => {
            inFlight.current = false;
        },
    };
}
