import type { HomeData } from '@add/shared';
import { formatEstimate } from '@add/shared';
import { Form, Head, Link } from '@inertiajs/react';
import { BackwardsPlan } from '@/components/backwards-plan';
import { Band } from '@/components/band';
import { Button } from '@/components/ui/button';
import { focus, overwhelmed } from '@/routes';
import focusRoutes from '@/routes/focus';

function OneThing({ children }: { children: React.ReactNode }) {
    return (
        <h1 className="border-now text-foreground border-l-2 pl-5 text-[clamp(1.8rem,4.6vw,2.9rem)] leading-[1.08] font-medium tracking-[-0.02em] text-balance">
            {children}
        </h1>
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
                <p className="text-muted-foreground pl-5 font-mono text-[13px]">
                    part-way through {session.intention.title.toLowerCase()}
                </p>
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
                <p className="text-muted-foreground pl-5 font-mono text-[13px]">
                    that is the whole answer
                </p>
            </div>
        );
    }

    const estimate = formatEstimate(rightNow.step.estimatedSeconds);

    return (
        <div className="space-y-6">
            <OneThing>{rightNow.step.title}</OneThing>
            <p className="text-muted-foreground pl-5 font-mono text-[13px]">
                {estimate ? `~${estimate}` : 'unestimated'} ·{' '}
                {rightNow.intention.title.toLowerCase()}
            </p>
            <div className="pl-5">
                <Form {...focusRoutes.start.form()}>
                    <input
                        type="hidden"
                        name="step_id"
                        value={rightNow.step.id}
                    />
                    <Button type="submit">Start</Button>
                </Form>
            </div>
        </div>
    );
}

export default function Home({ home: data }: { home: HomeData }) {
    const { rightNow, comingUp, needsAttention, restCount } = data;

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

                {comingUp && (
                    <Band label="Coming up">
                        <p>
                            {comingUp.intention.title}
                            <span className="text-muted-foreground font-mono text-[13px]">
                                {' '}
                                · {comingUp.inWords}
                            </span>
                        </p>
                        {comingUp.plan && (
                            <BackwardsPlan plan={comingUp.plan} />
                        )}
                    </Band>
                )}

                {needsAttention.length > 0 && (
                    <Band label="Needs attention">
                        <ul className="space-y-2">
                            {needsAttention.map((intention) => (
                                <li key={intention.id}>
                                    {intention.title}
                                    <span className="text-muted-foreground">
                                        {' '}
                                        — nobody has said yet what this means.
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </Band>
                )}

                <div className="border-border flex flex-wrap items-center gap-x-6 gap-y-2 border-t pt-5">
                    <p className="text-muted-foreground font-mono text-[13px]">
                        {restCount === 0
                            ? 'nothing else is waiting'
                            : `${restCount} other ${restCount === 1 ? 'thing' : 'things'}, none of which you need to think about`}
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
