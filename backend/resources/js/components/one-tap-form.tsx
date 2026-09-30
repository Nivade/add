import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useRef } from 'react';

/** Drops a second submit while the first is in flight; `disabled` would drop keyboard focus mid-request. */
export function OneTapForm({
    form,
    stepId,
    children,
}: {
    form: { action: string; method: 'post' };
    stepId?: string;
    children: (processing: boolean) => ReactNode;
}) {
    const inFlight = useRef(false);

    return (
        <Form
            {...form}
            onBefore={() => !inFlight.current}
            onStart={() => {
                inFlight.current = true;
            }}
            onFinish={() => {
                inFlight.current = false;
            }}
        >
            {({ processing }) => (
                <>
                    {stepId && (
                        <input type="hidden" name="step_id" value={stepId} />
                    )}
                    {children(processing)}
                </>
            )}
        </Form>
    );
}
