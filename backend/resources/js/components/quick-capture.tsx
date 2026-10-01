import { captureCopy } from '@add/shared';
import { Form } from '@inertiajs/react';
import type { KeyboardEvent, ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useSingleFlight } from '@/hooks/use-single-flight';
import { fieldClassName } from '@/lib/field';
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
export function CaptureHost({ trigger }: { trigger?: ReactNode }) {
    const [open, setOpen] = useState(false);
    const singleFlight = useSingleFlight();

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
        <Dialog open={open} onOpenChange={setOpen}>
            {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{captureCopy.question}</DialogTitle>
                    <DialogDescription>
                        {captureCopy.description}
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...store.form()}
                    {...singleFlight}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="flex flex-col gap-3"
                >
                    <textarea
                        name="body"
                        rows={4}
                        autoFocus
                        aria-label={captureCopy.question}
                        onKeyDown={submitOnModifiedEnter}
                        className={fieldClassName}
                    />
                    <div className="flex items-center justify-end gap-3">
                        <kbd className="text-muted-foreground text-small">
                            {saveShortcut()}
                        </kbd>
                        <Button type="submit">{captureCopy.save}</Button>
                    </div>
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export function QuickCapture() {
    return (
        <CaptureHost
            trigger={
                <Button variant="outline" size="sm" aria-label="Capture">
                    Capture
                    <kbd className="text-muted-foreground text-small ml-1">
                        c
                    </kbd>
                </Button>
            }
        />
    );
}
