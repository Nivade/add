import { useRef } from 'react';

/** Form callbacks that drop a second submit while the first is in flight, without disabling the focused button. */
export function useOneRequestAtATime() {
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
