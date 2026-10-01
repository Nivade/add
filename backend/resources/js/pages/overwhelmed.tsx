import type { OverwhelmedData } from '@add/shared';
import {
    nothingNeedsYou,
    overwhelmedCopy,
    restCountLine,
    smallestStepMeta,
} from '@add/shared';
import { Head, Link, router } from '@inertiajs/react';
import { KeyHint } from '@/components/key-hint';
import { Meta, OneThing, StartStep } from '@/components/one-thing';
import { quietLineClassName } from '@/components/responses';
import { useShortcuts } from '@/hooks/use-shortcuts';
import { home } from '@/routes';

/** No rail, no nav, no capture: the screen suppresses everything until this one step is done. */
export default function Overwhelmed({
    overwhelmed: { smallestStep, restCount },
}: {
    overwhelmed: OverwhelmedData;
}) {
    useShortcuts({ Escape: () => router.visit(home()) });

    return (
        <>
            <Head title="One thing" />

            <div className="bg-background text-foreground flex min-h-screen flex-col justify-center px-6 py-20">
                <div className="max-w-content mx-auto flex w-full flex-col items-start gap-8">
                    <p className="text-muted-foreground text-lead">One thing</p>

                    <OneThing>
                        {smallestStep
                            ? smallestStep.step.title
                            : nothingNeedsYou}
                    </OneThing>

                    {smallestStep && (
                        <>
                            <Meta>{smallestStepMeta(smallestStep.step)}</Meta>

                            <ul className="text-muted-foreground space-y-1">
                                {smallestStep.why.map((line) => (
                                    <li key={line}>{line}</li>
                                ))}
                            </ul>

                            <StartStep stepId={smallestStep.step.id} />
                        </>
                    )}

                    <p className="text-muted-foreground">
                        {restCountLine(restCount)}
                    </p>

                    <Link
                        href={home()}
                        aria-keyshortcuts="Escape"
                        className={quietLineClassName}
                    >
                        {overwhelmedCopy.back}
                        <KeyHint className="ml-2">Esc</KeyHint>
                    </Link>
                </div>
            </div>
        </>
    );
}
