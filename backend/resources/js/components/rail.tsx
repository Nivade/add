import type { RailData } from '@add/shared';
import { useEffect, useState } from 'react';

const DAY_START = 7 * 60;
const DAY_END = 23 * 60;
const HOURS = [8, 10, 12, 14, 16, 18, 20, 22];

function offset(minuteOfDay: number): number {
    const span = DAY_END - DAY_START;
    const clamped = Math.min(Math.max(minuteOfDay, DAY_START), DAY_END);

    return ((clamped - DAY_START) / span) * 100;
}

function clock(minuteOfDay: number): string {
    const hours = String(Math.floor(minuteOfDay / 60) % 24).padStart(2, '0');

    return `${hours}:${String(minuteOfDay % 60).padStart(2, '0')}`;
}

/** The server said what minute it is in their zone; this only counts forward from it. */
function useMinuteOfDay(from: number): number {
    const [minute, setMinute] = useState(from);

    useEffect(() => {
        setMinute(from);

        const startedAt = Date.now();
        const tick = setInterval(
            () =>
                setMinute(from + Math.floor((Date.now() - startedAt) / 60_000)),
            10_000,
        );

        return () => clearInterval(tick);
    }, [from]);

    return minute;
}

/** The rail is today, drawn to scale: the hours, where now sits, and the marks worth keeping in view. */
export function Rail({ rail }: { rail: RailData }) {
    const nowMinute = useMinuteOfDay(rail.nowMinute);
    const now = offset(nowMinute);
    const session =
        rail.sessionStartedMinute === null
            ? null
            : offset(rail.sessionStartedMinute);
    const leaveBy =
        rail.leaveByMinute === null ? null : offset(rail.leaveByMinute);

    return (
        <aside
            aria-label="Today"
            className="border-border relative hidden w-[108px] shrink-0 border-r py-8 select-none sm:block"
        >
            <div className="relative h-full">
                {HOURS.filter(
                    (hour) => Math.abs(hour * 60 - nowMinute) > 45,
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

                {leaveBy !== null && (
                    <div
                        className="text-foreground absolute left-0 flex w-full items-center gap-2 font-mono text-[11px]"
                        style={{ top: `${leaveBy}%` }}
                    >
                        <span
                            className="bg-foreground h-px w-5"
                            aria-hidden="true"
                        />
                        <span className="truncate">
                            leave
                            <span className="sr-only">
                                {' '}
                                at {rail.leaveByClock} for{' '}
                                {rail.appointmentTitle}
                            </span>
                        </span>
                    </div>
                )}

                <div
                    className="text-now absolute left-0 flex w-full items-center gap-2 font-mono text-[11px] font-medium tabular-nums"
                    style={{ top: `${now}%` }}
                >
                    <span className="bg-now h-0.5 w-5" aria-hidden="true" />
                    {clock(nowMinute)}
                </div>
            </div>
        </aside>
    );
}
