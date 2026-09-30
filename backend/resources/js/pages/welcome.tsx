import type { RailData } from '@add/shared';
import { clockOf, doneMinute, focusCopy, homeBands } from '@add/shared';
import { Head, Link } from '@inertiajs/react';
import { DayStrip } from '@/components/day-strip';
import { Button } from '@/components/ui/button';
import { Wordmark } from '@/components/wordmark';
import { login, register } from '@/routes';

const sampleRail: RailData = {
    nowMinute: 19 * 60 + 5,
    sessionStartedMinute: null,
    stepSeconds: 180,
    marks: [],
    appointmentTitle: null,
};

const sampleDoneAt = clockOf(
    doneMinute(sampleRail.nowMinute, sampleRail.stepSeconds ?? 0),
);

/** A still of home, so the promise is shown rather than described. */
function SampleHome() {
    return (
        <figure className="flex flex-col gap-3">
            <div
                inert
                className="bg-paper flex flex-col overflow-hidden rounded-2xl border sm:h-80 sm:flex-row"
            >
                <div aria-hidden="true" className="shrink-0 sm:w-30 sm:py-4">
                    <DayStrip
                        rail={sampleRail}
                        orientation="column"
                        live={false}
                        className="hidden h-full sm:block"
                    />
                    <DayStrip
                        rail={sampleRail}
                        orientation="row"
                        live={false}
                        className="h-10 sm:hidden"
                    />
                </div>
                <div className="flex flex-col items-start gap-4 p-6 sm:p-8">
                    <p className="text-2xl leading-tight font-semibold text-balance">
                        Put the laundry in the washing machine.
                    </p>
                    <p className="text-muted-foreground">
                        About 3 minutes, so done around {sampleDoneAt} if you
                        start now.
                    </p>
                    <Button
                        variant="now"
                        disabled
                        tabIndex={-1}
                        className="disabled:opacity-100"
                    >
                        {focusCopy.start}
                    </Button>
                    <div>
                        <p className="text-muted-foreground font-semibold">
                            {homeBands.why}
                        </p>
                        <p>Your parents arrive Saturday.</p>
                    </div>
                </div>
            </div>
            <figcaption className="text-muted-foreground">
                What opening the app looks like.
            </figcaption>
        </figure>
    );
}

export default function Welcome() {
    return (
        <>
            <Head title="add" />

            <div className="bg-background text-foreground flex min-h-screen flex-col px-5 py-6 sm:px-12 sm:py-8">
                <Wordmark />

                <main className="flex flex-1 items-center py-12">
                    <div className="grid w-full max-w-6xl items-center gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,30rem)] lg:gap-20">
                        <div className="flex flex-col items-start gap-8">
                            <h1 className="text-one-thing text-balance">
                                You should never have to work out what to do
                                next.
                            </h1>

                            <p className="text-muted-foreground text-lead max-w-md">
                                Write the thought down however it comes out. add
                                turns it into steps, then shows you one of them
                                at a time, with the reason it picked that one.
                            </p>

                            <div className="flex flex-wrap gap-3">
                                <Button asChild size="action">
                                    <Link href={register()}>
                                        Create an account
                                    </Link>
                                </Button>
                                <Button asChild size="action">
                                    <Link href={login()}>Log in</Link>
                                </Button>
                            </div>
                        </div>

                        <SampleHome />
                    </div>
                </main>

                <p className="text-muted-foreground">
                    Built for the days when starting is the hard part.
                </p>
            </div>
        </>
    );
}
