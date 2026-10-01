import { focusCopy } from '@add/shared';
import { Form } from '@inertiajs/react';
import { NowButton } from '@/components/now-button';
import focusRoutes from '@/routes/focus';

/** The one thing every screen answers with. One type scale, so no screen shouts louder than another. */
export function OneThing({ children }: { children: React.ReactNode }) {
    return (
        <h1
            tabIndex={-1}
            className="text-one-thing text-ink text-balance outline-none"
        >
            {children}
        </h1>
    );
}

/** The sentence under it: never a second answer, only what the first one costs. */
export function Meta({ children }: { children: React.ReactNode }) {
    return <p className="text-muted-foreground text-lead">{children}</p>;
}

export function StartStep({ stepId }: { stepId: string }) {
    return (
        <Form {...focusRoutes.start.form()}>
            <input type="hidden" name="step_id" value={stepId} />
            <NowButton type="submit">{focusCopy.start}</NowButton>
        </Form>
    );
}
