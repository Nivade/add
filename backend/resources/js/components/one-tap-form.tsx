import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useSingleFlight } from '@/hooks/use-single-flight';

/** One submit per tap, carrying what the person was looking at so a stale one is refused. */
export function OneTapForm({
    form,
    fields,
    children,
}: {
    form: { action: string; method: 'post' };
    fields: Record<string, string>;
    children: (processing: boolean) => ReactNode;
}) {
    const singleFlight = useSingleFlight();

    return (
        <Form {...form} {...singleFlight}>
            {({ processing }) => (
                <>
                    {Object.entries(fields).map(([name, value]) => (
                        <input
                            key={name}
                            type="hidden"
                            name={name}
                            value={value}
                        />
                    ))}
                    {children(processing)}
                </>
            )}
        </Form>
    );
}
