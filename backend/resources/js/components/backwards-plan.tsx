import type { BackwardsPlanData } from '@add/shared';
import { planRungLabels, rungMinutesLabel, rungMinutesNote } from '@add/shared';
import { Form } from '@inertiajs/react';
import { fieldClassName } from '@/lib/field';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import calendarEvents from '@/routes/calendar-events';
import intentions from '@/routes/intentions';

/** Arithmetic the person can overrule: every number here is either stated or labelled as assumed. */
export function BackwardsPlan({ plan }: { plan: BackwardsPlanData }) {
    return (
        <Form
            {...(plan.kind === 'calendar_event'
                ? calendarEvents.plan.form(plan.appointmentId)
                : intentions.plan.form(plan.appointmentId))}
            className="mt-4 space-y-2"
        >
            {plan.rungs.map((rung) => (
                <div key={rung.rung} className="flex items-baseline gap-3">
                    <span
                        className={
                            rung.alreadyPassed
                                ? 'text-muted-foreground text-numeric w-14 font-mono tabular-nums line-through'
                                : 'text-foreground text-numeric w-14 font-mono tabular-nums'
                        }
                    >
                        {rung.clock}
                    </span>
                    <span className="text-muted-foreground flex-1">
                        {planRungLabels[rung.rung]}
                    </span>
                    <label className="text-muted-foreground flex items-baseline gap-2">
                        <span className="sr-only">
                            {rungMinutesLabel(rung.rung)}
                        </span>
                        <input
                            type="number"
                            name={rung.rung}
                            min={0}
                            max={1440}
                            defaultValue={Math.round(rung.seconds / 60)}
                            className={cn(
                                fieldClassName,
                                'w-16 px-2 text-right font-mono tabular-nums',
                            )}
                        />
                        <span className="text-small w-20">
                            {rungMinutesNote(rung.assumed)}
                        </span>
                    </label>
                </div>
            ))}

            <div className="flex items-baseline gap-3">
                <span className="text-numeric w-14 font-mono font-semibold tabular-nums">
                    {plan.deadlineClock}
                </span>
                <span className="text-muted-foreground flex-1">
                    the appointment itself
                </span>
                <Button type="submit" variant="outline">
                    Save
                </Button>
            </div>
        </Form>
    );
}
