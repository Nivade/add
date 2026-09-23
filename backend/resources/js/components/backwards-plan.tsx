import type { BackwardsPlanData } from '@add/shared';
import { planRungLabels } from '@add/shared';
import { Form } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
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
                <div
                    key={rung.rung}
                    className="flex items-baseline gap-3 font-mono text-[13px]"
                >
                    <span
                        className={
                            rung.alreadyPassed
                                ? 'text-muted-foreground w-12 tabular-nums line-through'
                                : 'text-foreground w-12 tabular-nums'
                        }
                    >
                        {rung.clock}
                    </span>
                    <span className="text-muted-foreground flex-1">
                        {planRungLabels[rung.rung]}
                    </span>
                    <label className="text-muted-foreground flex items-baseline gap-2">
                        <span className="sr-only">
                            Minutes to {planRungLabels[rung.rung]}
                        </span>
                        <input
                            type="number"
                            name={rung.rung}
                            min={0}
                            max={1440}
                            defaultValue={Math.round(rung.seconds / 60)}
                            className="border-border focus-visible:ring-ring w-14 border-b bg-transparent py-0.5 text-right tabular-nums focus-visible:ring-1 focus-visible:outline-none"
                        />
                        <span className="w-16">
                            {rung.assumed ? 'min, assumed' : 'min, yours'}
                        </span>
                    </label>
                </div>
            ))}

            <div className="flex items-baseline gap-3 font-mono text-[13px]">
                <span className="text-now w-12 tabular-nums">
                    {plan.deadlineClock}
                </span>
                <span className="text-muted-foreground flex-1">
                    the appointment itself
                </span>
                <Button
                    type="submit"
                    variant="outline"
                    className="h-8 font-mono text-[11px] tracking-[0.08em] uppercase"
                >
                    Save
                </Button>
            </div>
        </Form>
    );
}
