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
            <p className="text-muted-foreground">
                You said you&apos;d do this.
            </p>
        );
    }

    return (
        <Form {...form} options={{ preserveScroll: true }}>
            <button
                type="submit"
                className="text-muted-foreground hover:text-foreground font-mono text-[13px] underline-offset-4 hover:underline"
            >
                I said I&apos;d do this
            </button>
        </Form>
    );
}
