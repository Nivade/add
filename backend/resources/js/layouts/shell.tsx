import { Link, router, usePage } from '@inertiajs/react';
import { DayStrip } from '@/components/day-strip';
import { KeyHint } from '@/components/key-hint';
import { QuickCapture } from '@/components/quick-capture';
import { UserMenu } from '@/components/user-menu';
import { Wordmark } from '@/components/wordmark';
import { NowMinuteContext, useMinuteOfDay } from '@/hooks/use-minute-of-day';
import { useShortcuts } from '@/hooks/use-shortcuts';
import { columnClassName } from '@/lib/column';
import { overwhelmed } from '@/routes';

/** The day, drawn beside a single column: one key to capture, and nothing else in the frame. */
export default function Shell({ children }: { children: React.ReactNode }) {
    const { rail } = usePage().props;
    const nowMinute = useMinuteOfDay(rail?.nowMinute ?? 0, rail !== null);

    useShortcuts({ o: () => router.visit(overwhelmed()) });

    return (
        <div className="bg-background text-foreground flex min-h-screen">
            {rail && (
                <DayStrip
                    rail={rail}
                    nowMinute={nowMinute}
                    orientation="column"
                    className="sticky top-0 hidden h-screen w-30 shrink-0 py-6 sm:block"
                />
            )}

            <div className="flex min-w-0 flex-1 flex-col">
                <header
                    className={`${columnClassName} flex flex-wrap items-center gap-x-3 gap-y-2 py-4`}
                >
                    <Wordmark />

                    <div className="ml-auto flex flex-wrap items-center gap-2">
                        <Link
                            href={overwhelmed()}
                            aria-keyshortcuts="o"
                            className="text-ink text-body inline-flex min-h-11 items-center gap-2 px-2 underline-offset-4 hover:underline"
                        >
                            {"I'm overwhelmed"}
                            <KeyHint>o</KeyHint>
                        </Link>
                        <QuickCapture />
                        <UserMenu />
                    </div>
                </header>

                {rail && (
                    <DayStrip
                        rail={rail}
                        nowMinute={nowMinute}
                        orientation="row"
                        className="h-10 sm:hidden"
                    />
                )}

                <main
                    className={`${columnClassName} flex-1 pt-8 pb-20 sm:pt-10`}
                >
                    <NowMinuteContext value={nowMinute}>
                        {children}
                    </NowMinuteContext>
                </main>
            </div>
        </div>
    );
}
