import type {
    HomeData,
    JustFinishedData,
    NeedsAttentionData,
} from '@add/shared';
import {
    commitmentCopy,
    focusCopy,
    homeBands,
    homeCopy,
    nothingNeedsYou,
    partWayLine,
    recurrenceLine,
    remindAfterCopy,
    restCountLine,
    returnCopy,
    rightNowMeta,
    sortingLine,
    waitingForResponses,
} from '@add/shared';
import { Form, Head, Link, usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import { BackwardsPlan } from '@/components/backwards-plan';
import { captureFieldClassName } from '@/components/capture-dialog';
import { CheckIn } from '@/components/check-in';
import { CommitmentRow } from '@/components/commitment-row';
import { Band } from '@/components/band';
import InputError from '@/components/input-error';
import { NotHere } from '@/components/not-here';
import { Meta, OneThing, StartStep } from '@/components/one-thing';
import {
    quietButtonClassName,
    quietLineClassName,
    Responses,
} from '@/components/responses';
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
                            className={cn(
                                captureFieldClassName,
                                'min-w-0 flex-1',
                            )}
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
        <Band label={homeBands.justFinished}>
            <p>{finished.title}</p>
            {finished.recurrenceEveryDays ? (
                <p className="text-muted-foreground mt-2">
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
                                className="text-muted-foreground"
                            >
                                {homeCopy.repeatEvery}
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
                            <span className="text-muted-foreground">days</span>
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
            <div className="flex flex-col items-start gap-5">
                <OneThing>
                    {session.returning
                        ? returnCopy.welcome
                        : (session.session.currentStep?.title ??
                          session.intention.title)}
                </OneThing>
                <Meta>
                    {session.returning
                        ? returnCopy.workingOn(session.intention.title)
                        : partWayLine(session.intention.title)}
                </Meta>
                <Button asChild size="action">
                    <Link href={focus()}>{focusCopy.continue}</Link>
                </Button>
            </div>
        );
    }

    if (!rightNow) {
        return (
            <div className="flex flex-col items-start gap-5">
                <OneThing>{nothingNeedsYou}</OneThing>
                <Meta>{homeCopy.wholeAnswer}</Meta>
            </div>
        );
    }

    return (
        <div className="flex flex-col items-start gap-5">
            <OneThing>{rightNow.step.title}</OneThing>
            <Meta>{rightNowMeta(rightNow.step, rightNow.intention.title)}</Meta>
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
        sortingCount,
    } = data;
    const { start, stop } = usePoll(
        3000,
        { only: ['home'] },
        { autoStart: false },
    );

    useEffect(() => {
        if (sortingCount > 0) {
            start();

            return stop;
        }
    }, [sortingCount, start, stop]);

    return (
        <>
            <Head title="Home" />

            <div className="flex flex-col gap-10 sm:gap-12">
                <section aria-label="Right now">
                    <RightNow {...data} />
                </section>

                <p
                    role="status"
                    className="text-muted-foreground empty:sr-only"
                >
                    {sortingCount > 0 && sortingLine(sortingCount)}
                </p>

                {!data.session && rightNow && rightNow.why.length > 0 && (
                    <Band label={homeBands.why}>
                        <ul className="space-y-1">
                            {rightNow.why.map((line) => (
                                <li key={line}>{line}</li>
                            ))}
                        </ul>
                        {rightNow.assumedPlace && (
                            <div className="mt-2">
                                <NotHere place={rightNow.assumedPlace} />
                            </div>
                        )}
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
                    <Band label={homeBands.beforeYouGo}>
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
                    <Band label={homeBands.comingUp}>
                        <p>
                            {comingUp.title}
                            <span className="text-muted-foreground">
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
                                <span className="text-muted-foreground">
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
                                    placeholder={remindAfterCopy.question}
                                    aria-label={remindAfterCopy.question}
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
                                    {remindAfterCopy.action}
                                </Button>
                            </Form>
                        )}
                    </Band>
                )}

                {needsAttention.length > 0 && (
                    <Band label={homeBands.needsAttention}>
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

                {data.checkIn && <CheckIn topic={data.checkIn} />}

                <div className="flex flex-wrap items-center gap-x-6 gap-y-2">
                    <p className="text-muted-foreground">
                        {restCountLine(restCount)}
                    </p>
                    {hasOpenCommitments && (
                        <Link
                            href={commitments.index()}
                            className={quietLineClassName}
                        >
                            {commitmentCopy.list}
                        </Link>
                    )}
                    <Link
                        href={overwhelmed()}
                        className={cn(quietLineClassName, 'ml-auto')}
                    >
                        {"I'm overwhelmed"}
                    </Link>
                </div>
            </div>
        </>
    );
}
