import { useEffect, useRef } from 'react';

const INTERACTIVE =
    'a[href], button, input, textarea, select, summary, [role="button"], [contenteditable="true"]';

function isTyping(target: EventTarget | null): boolean {
    return (
        target instanceof HTMLElement &&
        (target.isContentEditable ||
            ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))
    );
}

/** Enter already activates a focused control; the shortcut only answers when nothing is focused that would take it. */
function ownsEnter(target: EventTarget | null): boolean {
    return !(target instanceof Element && target.closest(INTERACTIVE));
}

function blockedByDialog(registeredIn: Element | null): boolean {
    return Array.from(document.querySelectorAll('[role="dialog"]')).some(
        (dialog) => registeredIn === null || !dialog.contains(registeredIn),
    );
}

/** Single keys for the controls on screen; attach the returned ref inside a dialog to keep its keys live while it is open. */
export function useShortcuts<T extends HTMLElement = HTMLElement>(
    map: Record<string, () => void>,
    enabled = true,
) {
    const registeredIn = useRef<T>(null);
    const latest = useRef(map);

    useEffect(() => {
        latest.current = map;
    });

    useEffect(() => {
        if (!enabled) {
            return;
        }

        function onKeyDown(event: KeyboardEvent) {
            const run = latest.current[event.key];

            if (
                !run ||
                event.defaultPrevented ||
                event.repeat ||
                event.metaKey ||
                event.ctrlKey ||
                event.altKey ||
                isTyping(event.target) ||
                (event.key === 'Enter' && !ownsEnter(event.target)) ||
                blockedByDialog(registeredIn.current)
            ) {
                return;
            }

            event.preventDefault();
            run();
        }

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [enabled]);

    return registeredIn;
}
