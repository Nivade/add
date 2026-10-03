/** The strip draws 06:00 to midnight; a minute outside it sits at the nearer edge. */
export const DAY_STRIP_START = 6 * 60;
export const DAY_STRIP_END = 24 * 60;

export function dayStripFraction(minuteOfDay: number): number {
    const clamped = Math.min(Math.max(minuteOfDay, DAY_STRIP_START), DAY_STRIP_END);

    return (clamped - DAY_STRIP_START) / (DAY_STRIP_END - DAY_STRIP_START);
}

export function clockOf(minuteOfDay: number): string {
    const hours = String(Math.floor(minuteOfDay / 60) % 24).padStart(2, '0');

    return `${hours}:${String(minuteOfDay % 60).padStart(2, '0')}`;
}

export function minuteOfClock(clock: string): number {
    return Number(clock.slice(0, 2)) * 60 + Number(clock.slice(3, 5));
}

export function doneMinute(nowMinute: number, seconds: number): number {
    return nowMinute + Math.max(1, Math.ceil(seconds / 60));
}
