import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { captureFieldClassName, CaptureDialog } from '@/components/capture-dialog';
import { store } from '@/routes/commitments';

/** Typed directly, same immediate confirmation as promoting a step: the person just said it themselves. */
export function CommitmentCapture() {
    const [open, setOpen] = useState(false);

    return (
        <CaptureDialog
            trigger="I'll do this"
            title="What did you just commit to?"
            description="Said out loud or typed, it counts the same either way."
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
                    name="description"
                    autoFocus
                    placeholder="I'll call Sarah Friday"
                    aria-label="What you committed to"
                    className={captureFieldClassName}
                />
                <Button type="submit" className="self-end">
                    Save
                </Button>
            </Form>
        </CaptureDialog>
    );
}
