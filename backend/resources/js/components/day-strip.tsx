import type { RailData, RailMarkData } from '@add/shared';
import {
    clockOf,
    dayStripFraction,
    dayStripLight,
    doneMinute,
    railMarkLabel,
    railSummary,
} from '@add/shared';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

const HOURS = [6, 8, 10, 12, 14, 16, 18, 20, 22];

/** An hour label this close to a mark, or to the now clock and its done line, would collide with them. */
const CROWDED_MINUTES = 45;
const CROWDED_NOW_MINUTES = 90;

/** Marks this close together share one label block, so their words never overlap. */
const SHARED_LABEL_MINUTES = 60;

function at(minuteOfDay: number): string {
    return `${dayStripFraction(minuteOfDay) * 100}%`;
}

function span(from: number, to: number): string {
    return `${Math.max(dayStripFraction(to) - dayStripFraction(from), 0.004) * 100}%`;
}

/** The stops sit at the same minutes in both themes; app.css swaps the colours. */
function band(direction: 'to bottom' | 'to right'): string {
    const stops = dayStripLight.map(
        ({ name, minute }) => `var(--strip-${name}) ${at(minute)}`,
    );

    return `linear-gradient(${direction}, ${stops.join(', ')})`;
}

function labelGroups(marks: RailMarkData[]): RailMarkData[][] {
    const groups: RailMarkData[][] = [];

    for (const mark of marks) {
        const last = groups.at(-1);

        if (last && mark.minute - last[0].minute <= SHARED_LABEL_MINUTES) {
            last.push(mark);
        } else {
            groups.push([mark]);
        }
    }

    return groups;
}

/** The server said what minute it is in their zone; this only counts forward from it. */
function useMinuteOfDay(from: number, live: boolean): number {
    const [minute, setMinute] = useState(from);

    useEffect(() => {
        setMinute(from);

        if (!live) {
            return;
        }

        const startedAt = Date.now();
        const tick = setInterval(
            () =>
                setMinute(from + Math.floor((Date.now() - startedAt) / 60_000)),
            10_000,
        );

        return () => clearInterval(tick);
    }, [from, live]);

    return minute;
}

type Drawing = { rail: RailData; nowMinute: number; doneAt: number | null };

function Column({ rail, nowMinute, doneAt }: Drawing) {
    const clear = (hour: number): boolean =>
        Math.abs(hour * 60 - nowMinute) > CROWDED_NOW_MINUTES &&
        rail.marks.every(
            (mark) => Math.abs(hour * 60 - mark.minute) > CROWDED_MINUTES,
        );

    return (
        <div aria-hidden="true" className="relative h-full">
            <div
                className="absolute inset-y-0 right-0 w-3"
                style={{ backgroundImage: band('to bottom') }}
            />

            {rail.sessionStartedMinute !== null && (
                <div
                    className="bg-ink/12 absolute inset-x-0"
                    style={{
                        top: at(rail.sessionStartedMinute),
                        height: span(rail.sessionStartedMinute, nowMinute),
                    }}
                />
            )}

            {doneAt !== null && (
                <div
                    className="bg-now/18 absolute inset-x-0"
                    style={{
                        top: at(nowMinute),
                        height: span(nowMinute, doneAt),
                    }}
                />
            )}

            {HOURS.filter(clear).map((hour) => (
                <span
                    key={hour}
                    className="text-muted-foreground text-small absolute left-3 -translate-y-1/2 font-mono tabular-nums"
                    style={{ top: at(hour * 60) }}
                >
                    {String(hour).padStart(2, '0')}
                </span>
            ))}

            {rail.marks.map((mark) => (
                <span
                    key={`${mark.rung}-${mark.minute}`}
                    className={cn(
                        'bg-ink absolute right-0',
                        mark.rung === null ? 'h-0.5 w-10' : 'h-px w-6',
                    )}
                    style={{ top: at(mark.minute) }}
                />
            ))}

            {labelGroups(rail.marks).map((group) => (
                <div
                    key={group[0].minute}
                    className="text-small absolute right-4 left-3 mt-1 space-y-0.5"
                    style={{ top: at(group[0].minute) }}
                >
                    {group.map((mark) => (
                        <p
                            key={`${mark.rung}-${mark.minute}`}
                            className={cn(
                                mark.rung === null &&
                                    'line-clamp-2 font-semibold',
                            )}
                        >
                            {railMarkLabel(mark, rail.appointmentTitle)}{' '}
                            <span className="font-mono tabular-nums">
                                {mark.clock}
                            </span>
                        </p>
                    ))}
                </div>
            ))}

            <div className="absolute inset-x-0" style={{ top: at(nowMinute) }}>
                <div className="bg-now h-0.5" />
                <p className="text-now text-numeric absolute bottom-1 left-3 font-mono font-semibold tabular-nums">
                    {clockOf(nowMinute)}
                </p>
                {doneAt !== null && (
                    <p className="text-now text-small absolute top-1.5 left-3 whitespace-nowrap">
                        done ~
                        <span className="font-mono tabular-nums">
                            {clockOf(doneAt)}
                        </span>
                    </p>
                )}
            </div>
        </div>
    );
}

function Row({ rail, nowMinute, doneAt }: Drawing) {
    const nowAt = dayStripFraction(nowMinute);

    return (
        <div aria-hidden="true" className="relative h-full">
            <div
                className="absolute inset-x-0 bottom-0 h-3"
                style={{ backgroundImage: band('to right') }}
            />

            {rail.sessionStartedMinute !== null && (
                <div
                    className="bg-ink/12 absolute inset-y-0"
                    style={{
                        left: at(rail.sessionStartedMinute),
                        width: span(rail.sessionStartedMinute, nowMinute),
                    }}
                />
            )}

            {doneAt !== null && (
                <div
                    className="bg-now/18 absolute inset-y-0"
                    style={{
                        left: at(nowMinute),
                        width: span(nowMinute, doneAt),
                    }}
                />
            )}

            {rail.marks.map((mark) => (
                <span
                    key={`${mark.rung}-${mark.minute}`}
                    className={cn(
                        'bg-ink absolute bottom-0',
                        mark.rung === null ? 'h-7 w-0.5' : 'h-5 w-px',
                    )}
                    style={{ left: at(mark.minute) }}
                />
            ))}

            <div
                className="bg-now absolute inset-y-0 w-0.5"
                style={{ left: at(nowMinute) }}
            />
            <p
                className={cn(
                    'text-now text-small absolute top-0.5 font-mono font-semibold tabular-nums',
                    nowAt < 0.75 ? 'ml-1.5' : 'mr-1.5',
                )}
                style={
                    nowAt < 0.75
                        ? { left: at(nowMinute) }
                        : { right: `${(1 - nowAt) * 100}%` }
                }
            >
                {clockOf(nowMinute)}
            </p>
        </div>
    );
}

/** Today, drawn to scale: the hours, where now sits, the step on offer and the marks worth keeping in view. */
export function DayStrip({
    rail,
    orientation,
    className,
    live = true,
}: {
    rail: RailData;
    orientation: 'column' | 'row';
    className?: string;
    live?: boolean;
}) {
    const nowMinute = useMinuteOfDay(rail.nowMinute, live);
    const doneAt =
        rail.stepSeconds === null
            ? null
            : doneMinute(nowMinute, rail.stepSeconds);
    const Drawn = orientation === 'column' ? Column : Row;

    return (
        <aside aria-label="Today" className={cn('select-none', className)}>
            <p className="sr-only">{railSummary(rail, nowMinute)}</p>
            <Drawn rail={rail} nowMinute={nowMinute} doneAt={doneAt} />
        </aside>
    );
}
