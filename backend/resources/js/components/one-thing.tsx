import {
    estimateLine,
    focusCopy,
    suggestedLabel,
    underWayEstimateLine,
} from '@add/shared';
import { Form } from '@inertiajs/react';
import { NowButton } from '@/components/now-button';
import { useNowMinute } from '@/hooks/use-minute-of-day';
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

/** Read off the layout's clock, so it says the same minute the day strip draws. */
export function EstimateLine({
    seconds,
    underWay = false,
}: {
    seconds: number | null;
    underWay?: boolean;
}) {
    const nowMinute = useNowMinute();

    return underWay
        ? underWayEstimateLine(seconds, nowMinute)
        : estimateLine(seconds, nowMinute);
}

export function SuggestedPill() {
    return (
        <span className="border-field text-muted-foreground text-small rounded-full border px-3 py-1">
            {suggestedLabel}
        </span>
    );
}

export function StartStep({
    stepId,
    suggested = false,
}: {
    stepId: string;
    suggested?: boolean;
}) {
    return (
        <Form
            {...focusRoutes.start.form()}
            className="flex flex-wrap items-center gap-3"
        >
            <input type="hidden" name="step_id" value={stepId} />
            <NowButton type="submit">{focusCopy.start}</NowButton>
            {suggested && <SuggestedPill />}
        </Form>
    );
}
