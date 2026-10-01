import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useSingleFlight } from '@/hooks/use-single-flight';

export function OneTapForm({
    form,
    stepId,
    children,
}: {
    form: { action: string; method: 'post' };
    stepId?: string;
    children: (processing: boolean) => ReactNode;
}) {
    const singleFlight = useSingleFlight();

    return (
        <Form {...form} {...singleFlight}>
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
