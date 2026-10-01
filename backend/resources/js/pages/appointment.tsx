import type { ComingUpData } from '@add/shared';
import { comingUpCopy, remindAfterCopy } from '@add/shared';
import { Form, Head, Link } from '@inertiajs/react';
import { BackwardsPlan } from '@/components/backwards-plan';
import { Band } from '@/components/band';
import InputError from '@/components/input-error';
import { OneThing } from '@/components/one-thing';
import { quietLineClassName } from '@/components/responses';
import { Button } from '@/components/ui/button';
import { fieldClassName } from '@/lib/field';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import calendarEvents from '@/routes/calendar-events';
import intentions from '@/routes/intentions';

/** A deadline read out of a sentence, confirmed, moved or dropped in one place. */
function InferredDeadline({ appointment }: { appointment: ComingUpData }) {
    return (
        <Band label={comingUpCopy.inferred}>
            <div className="flex flex-col items-start gap-4">
                <Form {...intentions.deadline.form(appointment.id)}>
                    <Button type="submit">{comingUpCopy.confirm}</Button>
                </Form>

                <Form
                    {...intentions.deadline.correct.form(appointment.id)}
                    className="flex flex-wrap items-end gap-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <label
                                htmlFor="deadline-at"
                                className="flex flex-col gap-1"
                            >
                                <span className="text-muted-foreground">
                                    {comingUpCopy.whenQuestion}
                                </span>
                                <input
                                    id="deadline-at"
                                    type="datetime-local"
                                    name="deadline_at"
                                    defaultValue={appointment.localAt}
                                    className={cn(fieldClassName, 'w-auto')}
                                />
                            </label>
                            <Button type="submit" aria-disabled={processing}>
                                {comingUpCopy.save}
                            </Button>
                            <InputError
                                message={errors.deadline_at}
                                className="basis-full"
                            />
                        </>
                    )}
                </Form>

                <Form {...intentions.deadline.correct.form(appointment.id)}>
                    <input type="hidden" name="deadline_at" value="" />
                    <Button type="submit" variant="quiet">
                        {comingUpCopy.noDeadline}
                    </Button>
                </Form>
            </div>
        </Band>
    );
}

function RemindAfter({ appointment }: { appointment: ComingUpData }) {
    return (
        <Band label={remindAfterCopy.question}>
            <Form
                {...calendarEvents.futureReminder.form(appointment.id)}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="flex flex-wrap items-center gap-3"
            >
                <input
                    name="message"
                    aria-label={remindAfterCopy.question}
                    className={cn(fieldClassName, 'w-auto min-w-0 flex-1')}
                />
                <input
                    type="number"
                    name="offset_minutes"
                    defaultValue={30}
                    aria-label="Minutes after"
                    className={cn(fieldClassName, 'w-20')}
                />
                <Button type="submit">{remindAfterCopy.action}</Button>
            </Form>
        </Band>
    );
}

export default function Appointment({
    appointment,
}: {
    appointment: ComingUpData;
}) {
    return (
        <>
            <Head title={appointment.title} />

            <div className="flex flex-col items-start gap-10 sm:gap-12">
                <div className="flex flex-col items-start gap-3">
                    <OneThing>{appointment.title}</OneThing>
                    <p className="text-muted-foreground text-lead">
                        {comingUpCopy.when(appointment.inWords)}
                        {appointment.kind === 'calendar_event' &&
                            ` ${comingUpCopy.fromCalendar}`}
                    </p>
                </div>

                {appointment.inferred && (
                    <InferredDeadline appointment={appointment} />
                )}

                {appointment.plan && (
                    <Band label={comingUpCopy.planFor}>
                        <BackwardsPlan plan={appointment.plan} />
                    </Band>
                )}

                {appointment.kind === 'calendar_event' && (
                    <RemindAfter appointment={appointment} />
                )}

                <Link href={home()} className={quietLineClassName}>
                    {comingUpCopy.back}
                </Link>
            </div>
        </>
    );
}
