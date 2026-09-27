import { Form } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { RouteFormDefinition } from '@/wayfinder';

export const quietButtonClassName =
    'h-8 font-mono text-[11px] tracking-[0.08em] uppercase';

/** Every answer posts to the same route and weighs the same: one row of equal buttons. */
export function Responses({
    action,
    responses,
}: {
    action: RouteFormDefinition<'post'>;
    responses: { value: string; label: string }[];
}) {
    return (
        <div className="flex flex-wrap gap-3">
            {responses.map(({ value, label }) => (
                <Form
                    key={value}
                    {...action}
                    transform={(data) => ({ ...data, response: value })}
                    options={{ preserveScroll: true }}
                >
                    <Button
                        type="submit"
                        variant="outline"
                        className={quietButtonClassName}
                    >
                        {label}
                    </Button>
                </Form>
            ))}
        </div>
    );
}
