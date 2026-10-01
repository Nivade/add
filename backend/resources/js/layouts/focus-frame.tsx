import { usePage } from '@inertiajs/react';
import { DayStrip } from '@/components/day-strip';
import { CaptureHost } from '@/components/quick-capture';
import { useMinuteOfDay } from '@/hooks/use-minute-of-day';
import { columnClassName } from '@/lib/column';

/** The day and the step, nothing else: no header pulls the eye off the work, and `c` still captures. */
export default function FocusFrame({
    children,
}: {
    children: React.ReactNode;
}) {
    const { rail } = usePage().props;
    const nowMinute = useMinuteOfDay(rail?.nowMinute ?? 0, rail !== null);

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
                {rail && (
                    <DayStrip
                        rail={rail}
                        nowMinute={nowMinute}
                        orientation="row"
                        className="h-10 sm:hidden"
                    />
                )}

                <main
                    className={`${columnClassName} flex-1 pt-8 pb-20 sm:pt-16`}
                >
                    {children}
                </main>
            </div>

            <CaptureHost />
        </div>
    );
}
