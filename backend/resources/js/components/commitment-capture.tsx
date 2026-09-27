import { entryCopy } from '@add/shared';
import {
    captureFieldClassName,
    CaptureFormDialog,
} from '@/components/capture-dialog';
import { store } from '@/routes/commitments';

const copy = entryCopy.commitment;

/** Typed directly, same immediate confirmation as promoting a step: the person just said it themselves. */
export function CommitmentCapture() {
    return (
        <CaptureFormDialog
            trigger="I'll do this"
            title={copy.question}
            description={copy.meta}
            form={store.form()}
        >
            {() => (
                <input
                    name="description"
                    autoFocus
                    placeholder={copy.placeholder}
                    aria-label={copy.label}
                    className={captureFieldClassName}
                />
            )}
        </CaptureFormDialog>
    );
}
