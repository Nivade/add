import { commitmentCopy } from '@add/shared';
import { Form } from '@inertiajs/react';
import { quietLineClassName } from '@/components/responses';
import type { RouteFormDefinition } from '@/wayfinder';

/** Off the one-tap path on purpose: a quiet line, never a button that competes with Start or Done. */
export function SaidIdDoThis({
    promised,
    form,
    fields = {},
}: {
    promised: boolean;
    form: RouteFormDefinition<'post'>;
    fields?: Record<string, string>;
}) {
    if (promised) {
        return (
            <p className="text-muted-foreground">{commitmentCopy.promised}</p>
        );
    }

    return (
        <Form {...form} options={{ preserveScroll: true }}>
            {Object.entries(fields).map(([name, value]) => (
                <input key={name} type="hidden" name={name} value={value} />
            ))}
            <button type="submit" className={quietLineClassName}>
                {commitmentCopy.promise}
            </button>
        </Form>
    );
}
