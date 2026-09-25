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
import { store } from '@/routes/future-reminders';

/** §15, time trigger only: a phrase carrying its own time, read back exactly as typed. */
export function FutureReminderCapture() {
    const [open, setOpen] = useState(false);

    return (
        <>
            <Button variant="outline" size="sm" onClick={() => setOpen(true)}>
                Remind future me
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>What should future you hear?</DialogTitle>
                        <DialogDescription>
                            Say when, in your own words.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        {...store.form()}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setOpen(false)}
                        resetOnSuccess
                        className="flex flex-col gap-3"
                    >
                        {({ errors }) => (
                            <>
                                <input
                                    name="text"
                                    autoFocus
                                    placeholder="Tomorrow at 5, buy dishwasher tablets"
                                    aria-label="What and when"
                                    aria-invalid={errors.text ? true : undefined}
                                    className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-base outline-none focus-visible:ring-[3px]"
                                />
                                {errors.text && (
                                    <p className="text-destructive text-sm">
                                        {errors.text}
                                    </p>
                                )}
                                <Button type="submit" className="self-end">
                                    Save
                                </Button>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>
        </>
    );
}
