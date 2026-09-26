import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { captureFieldClassName, CaptureDialog } from '@/components/capture-dialog';
import { store } from '@/routes/waiting-fors';

/** Same zero-friction shape as capture: who or what, and what for. No AI, no classification. */
export function WaitingForCapture() {
    const [open, setOpen] = useState(false);

    return (
        <CaptureDialog
            trigger="Waiting for"
            title="Who or what are you waiting on?"
            description="Nothing to do until they get back to you. This just keeps it from being forgotten."
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
                <input
                    name="subject"
                    autoFocus
                    placeholder="John"
                    aria-label="Who or what"
                    className={captureFieldClassName}
                />
                <input
                    name="note"
                    placeholder="the contract"
                    aria-label="What for"
                    className={captureFieldClassName}
                />
                <Button type="submit" className="self-end">
                    Save
                </Button>
            </Form>
        </CaptureDialog>
    );
}
