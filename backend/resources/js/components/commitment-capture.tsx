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
import { store } from '@/routes/commitments';

/** Typed directly, same immediate confirmation as promoting a step: the person just said it themselves. */
export function CommitmentCapture() {
    const [open, setOpen] = useState(false);

    return (
        <>
            <Button variant="outline" size="sm" onClick={() => setOpen(true)}>
                I'll do this
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>What did you just commit to?</DialogTitle>
                        <DialogDescription>
                            Said out loud or typed, it counts the same either
                            way.
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
                            name="description"
                            autoFocus
                            placeholder="I'll call Sarah Friday"
                            aria-label="What you committed to"
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
