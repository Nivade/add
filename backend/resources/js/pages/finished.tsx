import type { FinishedData } from '@add/shared';
import { finishedCopy, recurrenceLine } from '@add/shared';
import { Form, Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { KeyHint } from '@/components/key-hint';
import { Meta, OneThing, StartStep } from '@/components/one-thing';
import { Button } from '@/components/ui/button';
import { useShortcuts } from '@/hooks/use-shortcuts';
import { fieldClassName } from '@/lib/field';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import intentions from '@/routes/intentions';

const LINES_FROM_MS = 650;
const LINE_STEP_MS = 140;

const lineClassName =
    'animate-in fade-in slide-in-from-bottom-2 duration-300 [animation-fill-mode:both] motion-reduce:animate-none';

/** In sequence after the check, so the facts land one at a time; still under reduced motion. */
function lineAnimation(index: number): {
    className: string;
    style: React.CSSProperties;
} {
    return {
        className: lineClassName,
        style: { animationDelay: `${LINES_FROM_MS + index * LINE_STEP_MS}ms` },
    };
}

function DrawnCheck() {
    return (
        <svg
            viewBox="0 0 48 48"
            aria-hidden="true"
            className="text-now size-14"
        >
            <path
                d="M11 25.5 20 34.5 38 15"
                pathLength={1}
                fill="none"
                stroke="currentColor"
                strokeWidth={4}
                strokeLinecap="round"
                strokeLinejoin="round"
                className="finished-check"
            />
        </svg>
    );
}

function LeaveButton({ label }: { label: string }) {
    return (
        <Button asChild variant="outline" size="action">
            <Link href={home()} aria-keyshortcuts="Escape">
                {label}
                <KeyHint>Esc</KeyHint>
            </Link>
        </Button>
    );
}

function Recurrence({ intentionId }: { intentionId: string }) {
    const [asking, setAsking] = useState(false);

    if (!asking) {
        return (
            <Button variant="quiet" onClick={() => setAsking(true)}>
                {finishedCopy.comesBack}
            </Button>
        );
    }

    return (
        <Form
            {...intentions.recurrence.form(intentionId)}
            options={{ preserveScroll: true }}
            className="flex flex-wrap items-center gap-3"
        >
            {({ errors, processing }) => (
                <>
                    <label htmlFor="repeat-every-days">
                        {finishedCopy.every}
                    </label>
                    <input
                        id="repeat-every-days"
                        type="number"
                        name="every_days"
                        min={1}
                        max={365}
                        defaultValue={7}
                        autoFocus
                        className={cn(fieldClassName, 'w-20')}
                    />
                    <span>{finishedCopy.days}</span>
                    <Button type="submit" aria-disabled={processing}>
                        {finishedCopy.repeat}
                    </Button>
                    <InputError
                        message={errors.every_days}
                        className="basis-full"
                    />
                </>
            )}
        </Form>
    );
}

export default function Finished({
    finished: { intention, lines, next, recurrenceEveryDays },
}: {
    finished: FinishedData;
}) {
    useShortcuts({ Escape: () => router.visit(home()) });

    return (
        <>
            <Head title={finishedCopy.handled(intention.title)} />

            <div className="flex flex-col items-start gap-10">
                <div className="flex flex-col items-start gap-5">
                    <DrawnCheck />
                    <OneThing>{finishedCopy.handled(intention.title)}</OneThing>
                    <ul className="text-muted-foreground text-lead space-y-1">
                        {lines.map((line, index) => (
                            <li key={line} {...lineAnimation(index)}>
                                {line}
                            </li>
                        ))}
                    </ul>
                </div>

                {next ? (
                    <section
                        aria-labelledby="finished-next"
                        className={cn(
                            'flex flex-col items-start gap-4',
                            lineClassName,
                        )}
                        style={lineAnimation(lines.length).style}
                    >
                        <h2
                            id="finished-next"
                            className="text-muted-foreground text-band-heading"
                        >
                            {finishedCopy.next}
                        </h2>
                        <p className="text-lead font-semibold text-balance">
                            {next.step.title}
                        </p>
                        {next.why[0] && <Meta>{next.why[0]}</Meta>}
                        <div className="mt-2 flex flex-wrap gap-3">
                            <StartStep stepId={next.step.id} />
                            <LeaveButton label={finishedCopy.leave} />
                        </div>
                    </section>
                ) : (
                    <LeaveButton label={finishedCopy.home} />
                )}

                {recurrenceEveryDays ? (
                    <p className="text-muted-foreground">
                        {recurrenceLine(recurrenceEveryDays)}
                    </p>
                ) : (
                    <Recurrence intentionId={intention.id} />
                )}
            </div>
        </>
    );
}
