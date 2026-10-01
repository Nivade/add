import type { RailData, RailMarkData } from '@add/shared';
import {
    clockOf,
    dayStripFraction,
    dayStripLight,
    doneMinute,
    railMarkLabel,
    railSummary,
} from '@add/shared';
import type { CSSProperties, ReactNode } from 'react';
import { cn } from '@/lib/utils';

const HOURS = [6, 8, 10, 12, 14, 16, 18, 20, 22];

/** An hour label this close to a mark, or to the now clock and its done line, would collide with them. */
const CROWDED_MINUTES = 45;
const CROWDED_NOW_MINUTES = 90;

/** Marks this close together share one label block, so their words never overlap. */
const SHARED_LABEL_MINUTES = 60;

type Orientation = 'column' | 'row';

/** The same drawing on either axis: time runs down a column and across a row. */
const AXES = {
    column: {
        start: 'top',
        length: 'height',
        gradient: 'to bottom',
        band: 'inset-y-0 right-0 w-3',
        across: 'inset-x-0',
        tick: 'right-0 h-px w-6',
        appointmentTick: 'right-0 h-0.5 w-10',
        nowLine: 'inset-x-0 h-0.5',
    },
    row: {
        start: 'left',
        length: 'width',
        gradient: 'to right',
        band: 'inset-x-0 bottom-0 h-3',
        across: 'inset-y-0',
        tick: 'bottom-0 h-5 w-px',
        appointmentTick: 'bottom-0 h-7 w-0.5',
        nowLine: 'inset-y-0 w-0.5',
    },
} as const;

function percentOfDay(minuteOfDay: number): string {
    return `${dayStripFraction(minuteOfDay) * 100}%`;
}

function placed(
    orientation: Orientation,
    from: number,
    to?: number,
): CSSProperties {
    const { start, length } = AXES[orientation];
    const style: CSSProperties = { [start]: percentOfDay(from) };

    if (to !== undefined) {
        const share = Math.max(
            dayStripFraction(to) - dayStripFraction(from),
            0.004,
        );
        style[length] = `${share * 100}%`;
    }

    return style;
}

/** The stops sit at the same minutes in both themes; app.css swaps the colours. */
function stripGradient(orientation: Orientation): string {
    const stops = dayStripLight.map(
        ({ name, minute }) => `var(--strip-${name}) ${percentOfDay(minute)}`,
    );

    return `linear-gradient(${AXES[orientation].gradient}, ${stops.join(', ')})`;
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

type Drawing = { rail: RailData; nowMinute: number; doneAt: number | null };

/** The band, the session and step blocks, the marks and the now line: everything both axes draw. */
function Canvas({
    orientation,
    rail,
    nowMinute,
    doneAt,
    children,
}: Drawing & { orientation: Orientation; children: ReactNode }) {
    const axis = AXES[orientation];

    return (
        <div aria-hidden="true" className="relative h-full">
            <div
                className={cn('absolute', axis.band)}
                style={{ backgroundImage: stripGradient(orientation) }}
            />

            {rail.sessionStartedMinute !== null && (
                <div
                    className={cn('bg-ink/12 absolute', axis.across)}
                    style={placed(
                        orientation,
                        rail.sessionStartedMinute,
                        nowMinute,
                    )}
                />
            )}

            {doneAt !== null && (
                <div
                    className={cn('bg-now/18 absolute', axis.across)}
                    style={placed(orientation, nowMinute, doneAt)}
                />
            )}

            {rail.marks.map((mark) => (
                <span
                    key={`${mark.rung}-${mark.minute}`}
                    className={cn(
                        'bg-ink absolute',
                        mark.rung === null ? axis.appointmentTick : axis.tick,
                    )}
                    style={placed(orientation, mark.minute)}
                />
            ))}

            <div
                className={cn('bg-now absolute', axis.nowLine)}
                style={placed(orientation, nowMinute)}
            />

            {children}
        </div>
    );
}

function Column(drawing: Drawing) {
    const { rail, nowMinute, doneAt } = drawing;
    const clear = (hour: number): boolean =>
        Math.abs(hour * 60 - nowMinute) > CROWDED_NOW_MINUTES &&
        rail.marks.every(
            (mark) => Math.abs(hour * 60 - mark.minute) > CROWDED_MINUTES,
        );

    return (
        <Canvas orientation="column" {...drawing}>
            {HOURS.filter(clear).map((hour) => (
                <span
                    key={hour}
                    className="text-muted-foreground text-small absolute left-3 -translate-y-1/2 font-mono tabular-nums"
                    style={placed('column', hour * 60)}
                >
                    {String(hour).padStart(2, '0')}
                </span>
            ))}

            {labelGroups(rail.marks).map((group) => (
                <div
                    key={group[0].minute}
                    className="text-small absolute right-4 left-3 mt-1 space-y-0.5"
                    style={placed('column', group[0].minute)}
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
                                {clockOf(mark.minute)}
                            </span>
                        </p>
                    ))}
                </div>
            ))}

            <div
                className="absolute inset-x-0"
                style={placed('column', nowMinute)}
            >
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
        </Canvas>
    );
}

function Row(drawing: Drawing) {
    const { nowMinute } = drawing;
    const nearEnd = dayStripFraction(nowMinute) >= 0.75;

    return (
        <Canvas orientation="row" {...drawing}>
            <p
                className={cn(
                    'text-now text-small absolute top-0.5 font-mono font-semibold tabular-nums',
                    nearEnd ? 'mr-1.5' : 'ml-1.5',
                )}
                style={
                    nearEnd
                        ? {
                              right: `${(1 - dayStripFraction(nowMinute)) * 100}%`,
                          }
                        : placed('row', nowMinute)
                }
            >
                {clockOf(nowMinute)}
            </p>
        </Canvas>
    );
}

/** Today, drawn to scale: the hours, where now sits, the step on offer and the marks worth keeping in view. */
export function DayStrip({
    rail,
    nowMinute,
    orientation,
    className,
}: {
    rail: RailData;
    nowMinute: number;
    orientation: Orientation;
    className?: string;
}) {
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
