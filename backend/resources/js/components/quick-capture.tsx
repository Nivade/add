import { Form } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { captureFieldClassName, CaptureDialog } from '@/components/capture-dialog';
import { store } from '@/routes/captures';

function isTyping(target: EventTarget | null): boolean {
    return (
        target instanceof HTMLElement &&
        (target.isContentEditable ||
            ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))
    );
}

/** One field, no required anything: a thought costs nothing to drop here. */
export function QuickCapture() {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        function onKeyDown(event: KeyboardEvent) {
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
        <CaptureDialog
            trigger={
                <>
                    Capture
                    <kbd className="text-muted-foreground ml-1 text-xs">c</kbd>
                </>
            }
            title="What's on your mind?"
            description="Write it however it comes out. Sorting it out is the app's job."
            open={open}
            onOpenChange={setOpen}
        >
            <Form
                {...store.form()}
                options={{ preserveScroll: true }}
                onSuccess={() => setOpen(false)}
                resetOnSuccess
                className="flex flex-col gap-3"
            >
                <textarea
                    name="body"
                    rows={3}
                    autoFocus
                    aria-label="What's on your mind?"
                    className={captureFieldClassName}
                />
                <Button type="submit" className="self-end">
                    Capture
                </Button>
            </Form>
        </CaptureDialog>
    );
}
