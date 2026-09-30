import { Form } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { RouteFormDefinition } from '@/wayfinder';

/** A quiet line: off the one-tap path, and never competing with Start or Done. */
export const quietLineClassName =
    'text-muted-foreground hover:text-foreground inline-flex min-h-11 items-center text-left underline-offset-4 hover:underline';

/** Every answer posts to the same route and weighs the same: one row of equal buttons. */
export function Responses({
    action,
    responses,
}: {
    action: RouteFormDefinition<'post'>;
    responses: { value: string; label: string }[];
}) {
    return (
        <div className="flex flex-wrap gap-x-6">
            {responses.map(({ value, label }) => (
                <Form
                    key={value}
                    {...action}
                    transform={(data) => ({ ...data, response: value })}
                    options={{ preserveScroll: true }}
                >
                    <Button type="submit" variant="quiet">
                        {label}
                    </Button>
                </Form>
            ))}
        </div>
    );
}
