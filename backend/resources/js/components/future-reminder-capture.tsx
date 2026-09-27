import { entryCopy } from '@add/shared';
import {
    captureFieldClassName,
    CaptureFormDialog,
} from '@/components/capture-dialog';
import InputError from '@/components/input-error';
import { store } from '@/routes/future-reminders';

const copy = entryCopy.futureReminder;

/** §15, time trigger only: a phrase carrying its own time, read back exactly as typed. */
export function FutureReminderCapture() {
    return (
        <CaptureFormDialog
            trigger="Remind future me"
            title={copy.question}
            description={copy.meta}
            form={store.form()}
        >
            {(errors) => (
                <>
                    <input
                        name="text"
                        autoFocus
                        placeholder={copy.placeholder}
                        aria-label={copy.label}
                        aria-invalid={errors.text ? true : undefined}
                        className={captureFieldClassName}
                    />
                    <InputError message={errors.text} />
                </>
            )}
        </CaptureFormDialog>
    );
}
