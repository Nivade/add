import type {
    HomeData,
    JustFinishedData,
    NeedsAttentionData,
} from '@add/shared';
import {
    commitmentCopy,
    recurrenceLine,
    restCountLine,
    waitingForResponses,
} from '@add/shared';
import { Form, Head, Link } from '@inertiajs/react';
import { BackwardsPlan } from '@/components/backwards-plan';
import { captureFieldClassName } from '@/components/capture-dialog';
import { CommitmentRow } from '@/components/commitment-row';
import { Band } from '@/components/band';
import InputError from '@/components/input-error';
import { Meta, OneThing, StartStep, stepMeta } from '@/components/one-thing';
import { quietButtonClassName, Responses } from '@/components/responses';
import { SaidIdDoThis } from '@/components/said-id-do-this';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { focus, overwhelmed } from '@/routes';
import calendarEvents from '@/routes/calendar-events';
import commitments from '@/routes/commitments';
import intentions from '@/routes/intentions';
import reminders from '@/routes/reminders';
import waitingFors from '@/routes/waiting-fors';

function Clarify({ item }: { item: NeedsAttentionData }) {
    const fieldId = `clarify-${item.id}`;

    return (
        <Form
            {...intentions.clarification.form(item.id)}
            options={{ preserveScroll: true }}
            resetOnSuccess
            className="space-y-2"
        >
            {({ errors, processing }) => (
                <>
                    <p>{item.title}</p>
                    <label
                        htmlFor={fieldId}
                        className="text-muted-foreground block"
                    >
                        {item.clarifyingQuestion}
                    </label>
                    <div className="flex flex-wrap items-baseline gap-3">
                        <input
                            id={fieldId}
                            name="answer"
                            autoComplete="off"
                            aria-invalid={errors.answer ? true : undefined}
                            aria-describedby={
                                errors.answer ? `${fieldId}-error` : undefined
                            }
                            className="border-border focus-visible:ring-ring min-w-0 flex-1 border-b bg-transparent py-1 focus-visible:ring-1 focus-visible:outline-none"
                        />
                        <Button
                            type="submit"
                            variant="outline"
                            disabled={processing}
                            className={quietButtonClassName}
                        >
                            Answer
                        </Button>
                    </div>
                    <InputError
                        id={`${fieldId}-error`}
                        message={errors.answer}
                    />
                </>
            )}
        </Form>
    );
}

function WaitingFor({ item }: { item: NeedsAttentionData }) {
    return (
        <div className="space-y-2">
            <p>
                {item.title}
                {item.detail && (
                    <span className="text-muted-foreground">
                        {' '}
                        · {item.detail}
                    </span>
                )}
            </p>
            <Responses
                action={waitingFors.respond.form(item.id)}
                responses={waitingForResponses}
            />
        </div>
    );
}

function JustFinished({ finished }: { finished: JustFinishedData }) {
    return (
        <Band label="Just finished">
            <p>{finished.title}</p>
            {finished.recurrenceEveryDays ? (
                <p className="text-muted-foreground mt-2 font-mono text-[13px]">
                    {recurrenceLine(finished.recurrenceEveryDays)}
                </p>
            ) : (
                <Form
                    {...intentions.recurrence.form(finished.id)}
                    options={{ preserveScroll: true }}
                    className="mt-3 flex flex-wrap items-center gap-3"
                >
                    {({ errors }) => (
                        <>
                            <label
                                htmlFor="repeat-every-days"
                                className="text-muted-foreground font-mono text-[13px]"
                            >
                                Repeat every
                            </label>
                            <input
                                id="repeat-every-days"
                                type="number"
                                name="every_days"
                                min={1}
                                max={365}
                                defaultValue={7}
                                className={cn(captureFieldClassName, 'w-20')}
                            />
                            <span className="text-muted-foreground font-mono text-[13px]">
                                days
                            </span>
                            <Button
                                type="submit"
                                variant="outline"
                                className={quietButtonClassName}
                            >
                                Repeat
                            </Button>
                            <InputError
                                message={errors.every_days}
                                className="basis-full"
                            />
                        </>
                    )}
                </Form>
            )}
        </Band>
    );
}

function RightNow({ rightNow, session }: HomeData) {
    if (session) {
        return (
            <div className="space-y-6">
                <OneThing>
                    {session.session.currentStep?.title ??
                        session.intention.title}
                </OneThing>
                <Meta>
                    part-way through {session.intention.title.toLowerCase()}
                </Meta>
                <div className="pl-5">
                    <Button asChild>
                        <Link href={focus()}>Continue</Link>
                    </Button>
                </div>
            </div>
        );
    }

    if (!rightNow) {
        return (
            <div className="space-y-6">
                <OneThing>Nothing needs you right now.</OneThing>
                <Meta>that is the whole answer</Meta>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <OneThing>{rightNow.step.title}</OneThing>
            <Meta>
                {stepMeta(rightNow.step)} ·{' '}
                {rightNow.intention.title.toLowerCase()}
            </Meta>
            <StartStep stepId={rightNow.step.id} />
        </div>
    );
}

export default function Home({ home: data }: { home: HomeData }) {
    const {
        rightNow,
        rightNowIsCommitment,
        hasOpenCommitments,
        comingUp,
        reminder,
        justFinished,
        needsAttention,
        restCount,
    } = data;

    return (
        <>
            <Head title="Home" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-10 px-6 pt-6 pb-20 lg:mx-0 lg:ml-[8vw]">
                <section aria-labelledby="right-now">
                    <h2
                        id="right-now"
                        className="text-now mb-5 font-mono text-[11px] tracking-[0.18em] uppercase"
                    >
                        Right now
                    </h2>
                    <RightNow {...data} />
                </section>

                {!data.session && rightNow && rightNow.why.length > 0 && (
                    <Band label="Why this one">
                        <ul className="space-y-1">
                            {rightNow.why.map((line) => (
                                <li key={line}>{line}</li>
                            ))}
                        </ul>
                        <div className="mt-3">
                            <SaidIdDoThis
                                promised={rightNowIsCommitment}
                                form={intentions.commitment.form(
                                    rightNow.intention.id,
                                )}
                            />
                        </div>
                    </Band>
                )}

                {justFinished && <JustFinished finished={justFinished} />}

                {reminder && (
                    <Band label="Before you go">
                        <ul className="space-y-1">
                            {reminder.lines.map((line) => (
                                <li key={line}>{line}</li>
                            ))}
                        </ul>
                        <Form
                            {...reminders.dismiss.form(reminder.id)}
                            className="mt-3"
                        >
                            <Button
                                type="submit"
                                variant="outline"
                                className={quietButtonClassName}
                            >
                                Got it
                            </Button>
                        </Form>
                    </Band>
                )}

                {comingUp && (
                    <Band label="Coming up">
                        <p>
                            {comingUp.title}
                            <span className="text-muted-foreground font-mono text-[13px]">
                                {' '}
                                · {comingUp.inWords}
                                {comingUp.kind === 'calendar_event' &&
                                    ' · from your calendar'}
                            </span>
                        </p>
                        {comingUp.inferred && (
                            <Form
                                {...intentions.deadline.form(comingUp.id)}
                                className="mt-2 flex flex-wrap items-baseline gap-3"
                            >
                                <span className="text-muted-foreground font-mono text-[13px]">
                                    read from what you wrote
                                </span>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    className={quietButtonClassName}
                                >
                                    That{"'"}s right
                                </Button>
                            </Form>
                        )}
                        {comingUp.plan && (
                            <BackwardsPlan plan={comingUp.plan} />
                        )}
                        {comingUp.kind === 'calendar_event' && (
                            <Form
                                {...calendarEvents.futureReminder.form(
                                    comingUp.id,
                                )}
                                options={{ preserveScroll: true }}
                                resetOnSuccess
                                className="mt-3 flex flex-wrap items-center gap-3"
                            >
                                <input
                                    name="message"
                                    placeholder="What should future you hear?"
                                    aria-label="What should future you hear"
                                    className={cn(
                                        captureFieldClassName,
                                        'w-auto min-w-0 flex-1',
                                    )}
                                />
                                <input
                                    type="number"
                                    name="offset_minutes"
                                    defaultValue={30}
                                    aria-label="Minutes after"
                                    className={cn(
                                        captureFieldClassName,
                                        'w-20',
                                    )}
                                />
                                <Button
                                    type="submit"
                                    variant="outline"
                                    className={quietButtonClassName}
                                >
                                    Remind me after
                                </Button>
                            </Form>
                        )}
                    </Band>
                )}

                {needsAttention.length > 0 && (
                    <Band label="Needs attention">
                        <ul className="space-y-6">
                            {needsAttention.map((item) => (
                                <li key={item.id}>
                                    {item.kind === 'waiting_for' && (
                                        <WaitingFor item={item} />
                                    )}
                                    {item.kind === 'commitment' && (
                                        <CommitmentRow
                                            id={item.id}
                                            description={item.title}
                                            provenance={item.provenance}
                                            awaitingConfirmation={
                                                item.awaitingConfirmation
                                            }
                                        />
                                    )}
                                    {item.kind === 'intention' && (
                                        <Clarify item={item} />
                                    )}
                                </li>
                            ))}
                        </ul>
                    </Band>
                )}

                <div className="border-border flex flex-wrap items-center gap-x-6 gap-y-2 border-t pt-5">
                    <p className="text-muted-foreground font-mono text-[13px]">
                        {restCountLine(restCount)}
                    </p>
                    {hasOpenCommitments && (
                        <Link
                            href={commitments.index()}
                            className="text-muted-foreground hover:text-foreground font-mono text-[13px] underline-offset-4 hover:underline"
                        >
                            {commitmentCopy.list}
                        </Link>
                    )}
                    <Link
                        href={overwhelmed()}
                        className="text-muted-foreground hover:text-foreground ml-auto font-mono text-[11px] tracking-[0.16em] uppercase transition-colors"
                    >
                        {"I'm overwhelmed"}
                    </Link>
                </div>
            </div>
        </>
    );
}
