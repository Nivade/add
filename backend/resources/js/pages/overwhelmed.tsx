import type { OverwhelmedData } from '@add/shared';
import { restCountLine } from '@add/shared';
import { Head, Link } from '@inertiajs/react';
import { OneThing, StartStep } from '@/components/one-thing';
import { home } from '@/routes';

/** No rail, no nav, no capture: the screen suppresses everything until this one step is done. */
export default function Overwhelmed({
    overwhelmed: { smallestStep, restCount },
}: {
    overwhelmed: OverwhelmedData;
}) {
    return (
        <>
            <Head title="One thing" />

            <div className="bg-background text-foreground flex min-h-screen flex-col justify-center px-6 py-20">
                <div className="mx-auto flex w-full max-w-xl flex-col gap-8">
                    <p className="text-now font-mono text-[11px] tracking-[0.18em] uppercase">
                        One thing
                    </p>

                    <OneThing>
                        {smallestStep
                            ? smallestStep.step.title
                            : 'Nothing needs you right now.'}
                    </OneThing>

                    {smallestStep && (
                        <>
                            <ul className="text-muted-foreground space-y-1 pl-5 font-mono text-[13px]">
                                {smallestStep.why.map((line) => (
                                    <li key={line}>{line}</li>
                                ))}
                            </ul>

                            <StartStep stepId={smallestStep.step.id} />
                        </>
                    )}

                    <p className="text-muted-foreground border-border border-t pt-5 font-mono text-[13px]">
                        {restCountLine(restCount)}
                    </p>

                    <Link
                        href={home()}
                        className="text-muted-foreground hover:text-foreground font-mono text-[11px] tracking-[0.16em] uppercase transition-colors"
                    >
                        Back to home
                    </Link>
                </div>
            </div>
        </>
    );
}
