import { entryCopy } from '@add/shared';
import {
    captureFieldClassName,
    CaptureFormDialog,
} from '@/components/capture-dialog';
import { store } from '@/routes/waiting-fors';

const copy = entryCopy.waitingFor;

/** Same zero-friction shape as capture: who or what, and what for. No AI, no classification. */
export function WaitingForCapture() {
    return (
        <CaptureFormDialog
            trigger="Waiting for"
            title={copy.question}
            description={copy.meta}
            form={store.form()}
        >
            {() => (
                <>
                    <input
                        name="subject"
                        autoFocus
                        placeholder={copy.placeholder}
                        aria-label={copy.label}
                        className={captureFieldClassName}
                    />
                    <input
                        name="note"
                        placeholder={copy.notePlaceholder}
                        aria-label={copy.noteLabel}
                        className={captureFieldClassName}
                    />
                </>
            )}
        </CaptureFormDialog>
    );
}
