import { captureCopy } from '@add/shared';
import { Form } from '@inertiajs/react';
import type { KeyboardEvent, ReactNode, RefObject } from 'react';
import { useEffect, useRef, useState } from 'react';
import {
    captureFieldClassName,
    CaptureDialog,
} from '@/components/capture-dialog';
import { Button } from '@/components/ui/button';
import { store } from '@/routes/captures';

function isTyping(target: EventTarget | null): boolean {
    return (
        target instanceof HTMLElement &&
        (target.isContentEditable ||
            ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))
    );
}

function saveShortcut(): string {
    return typeof navigator !== 'undefined' &&
        /Mac|iPhone|iPad/.test(navigator.userAgent)
        ? '⌘↵'
        : 'Ctrl ↵';
}

function submitOnModifiedEnter(event: KeyboardEvent<HTMLTextAreaElement>) {
    if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        event.currentTarget.form?.requestSubmit();
    }
}

/** The one way in: every capture lands in this box, and the app sorts it. `c` opens it from anywhere. */
export function CaptureHost({
    trigger,
}: {
    trigger?: (
        open: () => void,
        ref: RefObject<HTMLButtonElement | null>,
    ) => ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const triggerRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        function onKeyDown(event: globalThis.KeyboardEvent) {
            if (event.key !== 'c' || event.metaKey || event.ctrlKey) {
                return;
            }

            if (isTyping(event.target)) {
                return;
            }

            event.preventDefault();
            setOpen(true);
        }

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    return (
        <>
            {trigger?.(() => setOpen(true), triggerRef)}

            <CaptureDialog
                title={captureCopy.question}
                description={captureCopy.description}
                open={open}
                onOpenChange={setOpen}
                onCloseAutoFocus={(event) => {
                    // Opened by its key, the dialog has nothing to hand focus back to but the button.
                    if (triggerRef.current) {
                        event.preventDefault();
                        triggerRef.current.focus();
                    }
                }}
            >
                <Form
                    {...store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="flex flex-col gap-3"
                >
                    {() => (
                        <>
                            <textarea
                                name="body"
                                rows={4}
                                autoFocus
                                aria-label={captureCopy.question}
                                onKeyDown={submitOnModifiedEnter}
                                className={captureFieldClassName}
                            />
                            <div className="flex items-center justify-end gap-3">
                                <kbd className="text-muted-foreground text-small">
                                    {saveShortcut()}
                                </kbd>
                                <Button type="submit">
                                    {captureCopy.save}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </CaptureDialog>
        </>
    );
}

export function QuickCapture() {
    return (
        <CaptureHost
            trigger={(open, ref) => (
                <Button
                    ref={ref}
                    variant="outline"
                    size="sm"
                    aria-label="Capture"
                    onClick={open}
                >
                    Capture
                    <kbd className="text-muted-foreground text-small ml-1">
                        c
                    </kbd>
                </Button>
            )}
        />
    );
}
