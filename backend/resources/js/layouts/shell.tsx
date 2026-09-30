import { usePage } from '@inertiajs/react';
import { CommitmentCapture } from '@/components/commitment-capture';
import { DayStrip } from '@/components/day-strip';
import { FutureReminderCapture } from '@/components/future-reminder-capture';
import { PasteCapture } from '@/components/paste-capture';
import { QuickCapture } from '@/components/quick-capture';
import { UserMenu } from '@/components/user-menu';
import { WaitingForCapture } from '@/components/waiting-for-capture';
import { Wordmark } from '@/components/wordmark';

/** The header shares the content's column, so the eye never crosses the screen. */
const columnClassName =
    'w-full px-5 sm:mr-6 sm:ml-[clamp(1.5rem,6vw,6rem)] sm:max-w-content sm:px-0';

/** The day, drawn beside a single column: one key to capture, and nothing else in the frame. */
export default function Shell({ children }: { children: React.ReactNode }) {
    const { rail } = usePage().props;

    return (
        <div className="bg-background text-foreground flex min-h-screen">
            {rail && (
                <DayStrip
                    rail={rail}
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
                        <QuickCapture />
                        <WaitingForCapture />
                        <CommitmentCapture />
                        <FutureReminderCapture />
                        <PasteCapture />
                        <UserMenu />
                    </div>
                </header>

                {rail && (
                    <DayStrip
                        rail={rail}
                        orientation="row"
                        className="h-10 sm:hidden"
                    />
                )}

                <main
                    className={`${columnClassName} flex-1 pt-8 pb-20 sm:pt-10`}
                >
                    {children}
                </main>
            </div>
        </div>
    );
}
