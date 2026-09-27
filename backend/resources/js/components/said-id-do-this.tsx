import { commitmentCopy } from '@add/shared';
import { Form } from '@inertiajs/react';
import type { RouteFormDefinition } from '@/wayfinder';

/** Off the one-tap path on purpose: a quiet line, never a button that competes with Start or Done. */
export function SaidIdDoThis({
    promised,
    form,
}: {
    promised: boolean;
    form: RouteFormDefinition<'post'>;
}) {
    if (promised) {
        return (
            <p className="text-muted-foreground">{commitmentCopy.promised}</p>
        );
    }

    return (
        <Form {...form} options={{ preserveScroll: true }}>
            <button
                type="submit"
                className="text-muted-foreground hover:text-foreground font-mono text-[13px] underline-offset-4 hover:underline"
            >
                {commitmentCopy.promise}
            </button>
        </Form>
    );
}
