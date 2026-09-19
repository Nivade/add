import type { RailData } from '@add/shared';

const DAY_START = 7 * 60;
const DAY_END = 23 * 60;
const HOURS = [8, 10, 12, 14, 16, 18, 20, 22];

function offset(minuteOfDay: number): number {
    const span = DAY_END - DAY_START;
    const clamped = Math.min(Math.max(minuteOfDay, DAY_START), DAY_END);

    return ((clamped - DAY_START) / span) * 100;
}

function minuteOfDay(iso: string): number {
    const at = new Date(iso);

    return at.getHours() * 60 + at.getMinutes();
}

function clock(iso: string): string {
    const at = new Date(iso);

    return `${String(at.getHours()).padStart(2, '0')}:${String(at.getMinutes()).padStart(2, '0')}`;
}

function sameDay(a: string, b: string): boolean {
    return new Date(a).toDateString() === new Date(b).toDateString();
}

/** The rail is today, drawn to scale: the hours, where now sits, and the marks worth keeping in view. */
export function Rail({ rail }: { rail: RailData }) {
    const now = offset(rail.minuteOfDay);
    const session = rail.sessionStartedAt
        ? offset(minuteOfDay(rail.sessionStartedAt))
        : null;
    const deadlineToday =
        rail.deadlineAt && sameDay(rail.deadlineAt, rail.nowAt)
            ? offset(minuteOfDay(rail.deadlineAt))
            : null;

    return (
        <aside
            aria-label="Today"
            className="border-border relative hidden w-[108px] shrink-0 border-r py-8 select-none sm:block"
        >
            <div className="relative h-full">
                {HOURS.filter(
                    (hour) => Math.abs(hour * 60 - rail.minuteOfDay) > 45,
                ).map((hour) => (
                    <div
                        key={hour}
                        className="text-muted-foreground absolute left-0 flex w-full items-center gap-2 font-mono text-[11px] tabular-nums"
                        style={{ top: `${offset(hour * 60)}%` }}
                    >
                        <span
                            className="bg-border h-px w-3"
                            aria-hidden="true"
                        />
                        {String(hour).padStart(2, '0')}
                    </div>
                ))}

                {session !== null && (
                    <div
                        className="bg-now/25 absolute left-[5px] w-px"
                        style={{
                            top: `${session}%`,
                            height: `${Math.max(now - session, 0.4)}%`,
                        }}
                        aria-hidden="true"
                    />
                )}

                {deadlineToday !== null && (
                    <div
                        className="text-foreground absolute left-0 flex w-full items-center gap-2 font-mono text-[11px]"
                        style={{ top: `${deadlineToday}%` }}
                    >
                        <span
                            className="bg-foreground h-px w-5"
                            aria-hidden="true"
                        />
                        due
                    </div>
                )}

                <div
                    className="text-now absolute left-0 flex w-full items-center gap-2 font-mono text-[11px] font-medium tabular-nums"
                    style={{ top: `${now}%` }}
                >
                    <span className="bg-now h-0.5 w-5" aria-hidden="true" />
                    {clock(rail.nowAt)}
                </div>
            </div>
        </aside>
    );
}
