import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { store } from '@/routes/waiting-fors';

/** Same zero-friction shape as capture: who or what, and what for. No AI, no classification. */
export function WaitingForCapture() {
    const [open, setOpen] = useState(false);

    return (
        <>
            <Button variant="outline" size="sm" onClick={() => setOpen(true)}>
                Waiting for
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Who or what are you waiting on?
                        </DialogTitle>
                        <DialogDescription>
                            Nothing to do until they get back to you. This just
                            keeps it from being forgotten.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        {...store.form()}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setOpen(false)}
                        resetOnSuccess
                        className="flex flex-col gap-3"
                    >
                        <input
                            name="subject"
                            autoFocus
                            placeholder="John"
                            aria-label="Who or what"
                            className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-base outline-none focus-visible:ring-[3px]"
                        />
                        <input
                            name="note"
                            placeholder="the contract"
                            aria-label="What for"
                            className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-base outline-none focus-visible:ring-[3px]"
                        />
                        <Button type="submit" className="self-end">
                            Save
                        </Button>
                    </Form>
                </DialogContent>
            </Dialog>
        </>
    );
}
