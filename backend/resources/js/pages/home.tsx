import type { HomeData, IntentionData } from '@add/shared';
import { restCountLine } from '@add/shared';
import { Form, Head, Link } from '@inertiajs/react';
import { BackwardsPlan } from '@/components/backwards-plan';
import { Band } from '@/components/band';
import InputError from '@/components/input-error';
import { Meta, OneThing, StartStep, stepMeta } from '@/components/one-thing';
import { Button } from '@/components/ui/button';
import { focus, overwhelmed } from '@/routes';
import intentions from '@/routes/intentions';
import reminders from '@/routes/reminders';

function Clarify({ intention }: { intention: IntentionData }) {
    const fieldId = `clarify-${intention.id}`;

    return (
        <Form
            {...intentions.clarification.form(intention.id)}
            options={{ preserveScroll: true }}
            resetOnSuccess
            className="space-y-2"
        >
            {({ errors, processing }) => (
                <>
                    <p>{intention.title}</p>
                    <label
                        htmlFor={fieldId}
                        className="text-muted-foreground block"
                    >
                        {intention.clarifyingQuestion}
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
                            className="h-8 font-mono text-[11px] tracking-[0.08em] uppercase"
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
    const { rightNow, comingUp, reminder, needsAttention, restCount } = data;

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
                    </Band>
                )}

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
                                className="h-8 font-mono text-[11px] tracking-[0.08em] uppercase"
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
                                    className="h-8 font-mono text-[11px] tracking-[0.08em] uppercase"
                                >
                                    That{"'"}s right
                                </Button>
                            </Form>
                        )}
                        {comingUp.plan && (
                            <BackwardsPlan plan={comingUp.plan} />
                        )}
                    </Band>
                )}

                {needsAttention.length > 0 && (
                    <Band label="Needs attention">
                        <ul className="space-y-6">
                            {needsAttention.map((intention) => (
                                <li key={intention.id}>
                                    <Clarify intention={intention} />
                                </li>
                            ))}
                        </ul>
                    </Band>
                )}

                <div className="border-border flex flex-wrap items-center gap-x-6 gap-y-2 border-t pt-5">
                    <p className="text-muted-foreground font-mono text-[13px]">
                        {restCountLine(restCount)}
                    </p>
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
