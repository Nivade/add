import { Form } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
        <>
            <Button variant="outline" size="sm" onClick={() => setOpen(true)}>
                Capture
                <kbd className="text-muted-foreground ml-1 text-xs">c</kbd>
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>What's on your mind?</DialogTitle>
                        <DialogDescription>
                            Write it however it comes out. Sorting it out is the
                            app's job.
                        </DialogDescription>
                    </DialogHeader>
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
                            className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-base outline-none focus-visible:ring-[3px]"
                        />
                        <Button type="submit" className="self-end">
                            Capture
                        </Button>
                    </Form>
                </DialogContent>
            </Dialog>
        </>
    );
}
