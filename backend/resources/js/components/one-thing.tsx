import { formatEstimate } from '@add/shared';
import { Form } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import focusRoutes from '@/routes/focus';

/** The one thing every screen answers with. One type scale, so no screen shouts louder than another. */
export function OneThing({ children }: { children: React.ReactNode }) {
    return (
        <h1 className="border-now text-foreground border-l-2 pl-5 text-[clamp(1.8rem,4.6vw,2.9rem)] leading-[1.08] font-medium tracking-[-0.02em] text-balance">
            {children}
        </h1>
    );
}

/** The quiet line under it: never a second answer, only what the first one costs. */
export function Meta({ children }: { children: React.ReactNode }) {
    return (
        <p className="text-muted-foreground pl-5 font-mono text-[13px]">
            {children}
        </p>
    );
}

/** An unestimated step says so rather than going quiet, and a guessed one says who guessed. */
export function stepMeta(step: {
    estimatedSeconds: number | null;
    generated: boolean;
}): string {
    const estimate = formatEstimate(step.estimatedSeconds);

    return (
        (estimate ? `~${estimate}` : 'unestimated') +
        (step.generated ? ' · suggested' : '')
    );
}

export function StartStep({ stepId }: { stepId: string }) {
    return (
        <div className="pl-5">
            <Form {...focusRoutes.start.form()}>
                <input type="hidden" name="step_id" value={stepId} />
                <Button type="submit">Start</Button>
            </Form>
        </div>
    );
}
