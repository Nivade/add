import type { HomeData } from '@add/shared';
import { formatEstimate } from '@add/shared';
import { Form, Head, Link } from '@inertiajs/react';
import { Band } from '@/components/band';
import { Button } from '@/components/ui/button';
import { focus, home } from '@/routes';
import focusRoutes from '@/routes/focus';

function OneThing({ children }: { children: React.ReactNode }) {
    return (
        <h1 className="border-now border-l-2 pl-4 text-[clamp(1.75rem,5vw,2.75rem)] leading-[1.1] font-semibold tracking-tight text-balance">
            {children}
        </h1>
    );
}

function RightNow({ rightNow, session }: HomeData) {
    if (session) {
        return (
            <div className="space-y-5">
                <OneThing>
                    {session.session.currentStep?.title ??
                        session.intention.title}
                </OneThing>
                <p className="text-muted-foreground">
                    You are part-way through {session.intention.title}.
                </p>
                <Button asChild size="lg">
                    <Link href={focus()}>Continue</Link>
                </Button>
            </div>
        );
    }

    if (!rightNow) {
        return (
            <div className="space-y-5">
                <OneThing>Nothing needs you right now.</OneThing>
                <p className="text-muted-foreground">
                    That is the whole answer. Come back when something turns up.
                </p>
            </div>
        );
    }

    const estimate = formatEstimate(rightNow.step.estimatedSeconds);

    return (
        <div className="space-y-5">
            <OneThing>{rightNow.step.title}</OneThing>
            {estimate && (
                <p className="text-muted-foreground">About {estimate}.</p>
            )}
            <Form {...focusRoutes.start.form()}>
                <input type="hidden" name="step_id" value={rightNow.step.id} />
                <Button type="submit" size="lg">
                    Start
                </Button>
            </Form>
        </div>
    );
}

export default function Home({ home: data }: { home: HomeData }) {
    const { rightNow, comingUp, needsAttention, restCount } = data;

    return (
        <>
            <Head title="Home" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-10 px-6 py-10">
                <section aria-labelledby="right-now">
                    <h2
                        id="right-now"
                        className="text-now mb-4 text-xs font-medium tracking-[0.18em] uppercase"
                    >
                        Right now
                    </h2>
                    <RightNow {...data} />
                </section>

                {rightNow && rightNow.why.length > 0 && (
                    <Band label="Why this one">
                        <ul className="space-y-1">
                            {rightNow.why.map((line) => (
                                <li key={line}>{line}</li>
                            ))}
                        </ul>
                        <p className="text-muted-foreground mt-3 text-sm">
                            Part of {rightNow.intention.title}
                            {rightNow.intention.why
                                ? ` — ${rightNow.intention.why}`
                                : ''}
                            .
                        </p>
                    </Band>
                )}

                {comingUp && (
                    <Band label="Coming up">
                        <p>
                            {comingUp.intention.title}
                            <span className="text-muted-foreground">
                                , {comingUp.inWords}
                            </span>
                        </p>
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

                <p className="text-muted-foreground border-border border-t pt-4 text-sm">
                    {restCount === 0
                        ? 'Nothing else is waiting.'
                        : `${restCount} other ${restCount === 1 ? 'thing' : 'things'}, none of which you need to think about.`}
                </p>
            </div>
        </>
    );
}

Home.layout = {
    breadcrumbs: [{ title: 'Home', href: home() }],
};
