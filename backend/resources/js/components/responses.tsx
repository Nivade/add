import { Form } from '@inertiajs/react';
import { Button, buttonVariants } from '@/components/ui/button';
import { useSingleFlight } from '@/hooks/use-single-flight';
import { cn } from '@/lib/utils';
import type { RouteFormDefinition } from '@/wayfinder';

/** A quiet line: off the one-tap path, and never competing with Start or Done. */
export const quietLineClassName = cn(buttonVariants({ variant: 'quiet' }));

/** Every answer posts to the same route and weighs the same: one row of equal buttons. */
export function Responses({
    action,
    responses,
    name = 'response',
    id,
}: {
    action: RouteFormDefinition<'post'>;
    responses: { value: string; label: string }[];
    name?: string;
    id?: string;
}) {
    const singleFlight = useSingleFlight();

    return (
        <div id={id} className="flex flex-wrap gap-x-6">
            {responses.map(({ value, label }) => (
                <Form
                    key={value}
                    {...action}
                    {...singleFlight}
                    transform={(data) => ({ ...data, [name]: value })}
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
