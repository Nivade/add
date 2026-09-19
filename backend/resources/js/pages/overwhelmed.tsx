import type { OverwhelmedData } from '@add/shared';
import { Form, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import focusRoutes from '@/routes/focus';

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

                    {smallestStep ? (
                        <>
                            <h1 className="border-now text-foreground border-l-2 pl-5 text-[clamp(1.8rem,4.6vw,2.9rem)] leading-[1.08] font-medium tracking-[-0.02em] text-balance">
                                {smallestStep.step.title}
                            </h1>

                            <ul className="text-muted-foreground space-y-1 pl-5 font-mono text-[13px]">
                                {smallestStep.why.map((line) => (
                                    <li key={line}>{line}</li>
                                ))}
                            </ul>

                            <div className="pl-5">
                                <Form {...focusRoutes.start.form()}>
                                    <input
                                        type="hidden"
                                        name="step_id"
                                        value={smallestStep.step.id}
                                    />
                                    <Button type="submit">Start</Button>
                                </Form>
                            </div>
                        </>
                    ) : (
                        <h1 className="border-now text-foreground border-l-2 pl-5 text-[clamp(1.8rem,4.6vw,2.9rem)] leading-[1.08] font-medium tracking-[-0.02em] text-balance">
                            Nothing needs you right now.
                        </h1>
                    )}

                    <p className="text-muted-foreground border-border border-t pt-5 font-mono text-[13px]">
                        {restCount === 0
                            ? 'nothing else is waiting'
                            : `${restCount} other ${restCount === 1 ? 'thing' : 'things'}, none of which you need to think about`}
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
