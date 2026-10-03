import type { HomeData, RailData, RailMarkData } from '@add/shared';

function minuteOfClock(clock: string): number {
  const [hours, minutes] = clock.split(':').map(Number);

  return hours * 60 + minutes;
}

function minuteOf(at: Date): number {
  return at.getHours() * 60 + at.getMinutes();
}

function localDate(now: Date): string {
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');

  return `${now.getFullYear()}-${month}-${day}`;
}

/** The web draws the strip from a server prop; the home answer already holds everything it needs. */
export function railOf(home: HomeData, now: Date): RailData {
  const today = localDate(now);
  const { comingUp, session, rightNow } = home;
  const marks: RailMarkData[] = [];

  if (comingUp?.plan) {
    for (const rung of comingUp.plan.rungs) {
      if (rung.at.startsWith(today)) {
        marks.push({ rung: rung.rung, minute: minuteOfClock(rung.clock) });
      }
    }

    if (comingUp.localAt.startsWith(today)) {
      marks.push({
        rung: null,
        minute: minuteOfClock(comingUp.localAt.slice(11, 16)),
      });
    }
  }

  return {
    nowMinute: minuteOf(now),
    sessionStartedMinute: session ? minuteOf(new Date(session.session.startedAt)) : null,
    stepSeconds: session ? null : (rightNow?.step.estimatedSeconds ?? null),
    marks,
    appointmentTitle: marks.length === 0 ? null : (comingUp?.title ?? null),
  };
}
