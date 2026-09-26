import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { captureFieldClassName, CaptureDialog } from '@/components/capture-dialog';
import { store } from '@/routes/future-reminders';

/** §15, time trigger only: a phrase carrying its own time, read back exactly as typed. */
export function FutureReminderCapture() {
    const [open, setOpen] = useState(false);

    return (
        <CaptureDialog
            trigger="Remind future me"
            title="What should future you hear?"
            description="Say when, in your own words."
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
                {({ errors }) => (
                    <>
                        <input
                            name="text"
                            autoFocus
                            placeholder="Tomorrow at 5, buy dishwasher tablets"
                            aria-label="What and when"
                            aria-invalid={errors.text ? true : undefined}
                            className={captureFieldClassName}
                        />
                        {errors.text && (
                            <p className="text-destructive text-sm">{errors.text}</p>
                        )}
                        <Button type="submit" className="self-end">
                            Save
                        </Button>
                    </>
                )}
            </Form>
        </CaptureDialog>
    );
}
