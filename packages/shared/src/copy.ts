import type {
    CaptureKind,
    CheckInAnswer,
    CheckInTopic,
    CommitmentProvenance,
    CommitmentResponse,
    Place,
    PlanRung,
    RailData,
    RailMarkData,
    StuckReason,
    WaitingForResponse,
} from './generated';
import { clockOf, doneMinute } from './day';
import { formatEstimate } from './estimate';

/** The count may be stated, never enumerated, so both frontends state it the same way. */
export function restCountLine(count: number): string {
    if (count === 0) {
        return 'Nothing else is waiting.';
    }

    return `${count} other ${count === 1 ? 'thing' : 'things'}, none of which you need to think about.`;
}

/** Settings names the zone every clock is read in, and where it came from. */
export function timezoneLine(zone: string): string {
    return `Times are read in ${zone}, taken from this device.`;
}

/** Said while a capture is still with the model, so it never looks lost. */
export function sortingLine(count: number): string {
    return count === 1
        ? 'Sorting the thought you just wrote down.'
        : `Sorting the ${count} thoughts you just wrote down.`;
}

/** What stopped sorting is said once, and always with the reassurance that nothing was lost. */
export function unsortedLine(count: number, consented: boolean): { line: string; action: string | null } {
    if (!consented) {
        return { line: 'Sorting what you write needs AI turned on. Nothing you wrote is lost.', action: 'Turn it on' };
    }

    return {
        line: `${count} ${count === 1 ? 'thing' : 'things'} you wrote could not be sorted. Nothing is lost.`,
        action: null,
    };
}

/** The read-back says what the app guessed, so no inferred kind becomes fact silently. */
export function sortedLine(kind: CaptureKind, detail: string | null): string {
    switch (kind) {
        case 'waiting_for':
            return detail ? `Saved as something you are waiting on from ${detail}.` : 'Saved as something you are waiting on.';
        case 'promise':
            return 'Saved as something you said you would do.';
        case 'reminder':
            return detail ? `Saved as a reminder for ${detail}.` : 'Saved as a reminder.';
        case 'not_for_you':
            return 'Nothing in this looks like it needs you.';
        case 'thought':
            return 'Saved as a thought.';
    }
}

/** In the order offered when the person says the sort was wrong; not-for-you is never theirs to pick. */
export const captureKindChoices: { value: Exclude<CaptureKind, 'not_for_you'>; label: string }[] = [
    { value: 'thought', label: "It's a thought" },
    { value: 'waiting_for', label: "I'm waiting on it" },
    { value: 'promise', label: "I said I'd do it" },
    { value: 'reminder', label: 'Remind me' },
];

export const sortedCopy = {
    right: "That's right",
    notRight: 'Not right?',
    keep: 'Keep it anyway',
} as const;

/** Past three, the read-back is a count, never a longer list. */
export function sortedMoreLine(count: number): string {
    return count === 1
        ? '1 more thing you wrote is waiting to be checked.'
        : `${count} more things you wrote are waiting to be checked.`;
}

/** Coming back is welcomed, never timed: both frontends say it in these words. */
export const returnCopy = {
    welcome: 'Welcome back.',
    partWay: (title: string): string => `Part-way through ${title}.`,
    paused: 'Paused.',
    pausedMeta: 'Continue whenever you are ready.',
    workingOn: (title: string): string => `You were working on ${title}.`,
    meta: (title: string, stepsDone: number): string =>
        stepsDone === 0
            ? returnCopy.workingOn(title)
            : `${returnCopy.workingOn(title)} You had done ${stepsDone} ${stepsDone === 1 ? 'step' : 'steps'}.`,
};

export const planRungLabels: Record<PlanRung, string> = {
    find_things: 'find what you need',
    get_ready: 'get ready',
    leave: 'leave',
};

/** Short enough to sit beside a tick on the day strip. */
export const railRungLabels: Record<PlanRung, string> = {
    find_things: 'find things',
    get_ready: 'get ready',
    leave: 'leave',
};

export function railMarkLabel(mark: RailMarkData, appointmentTitle: string | null): string {
    return mark.rung === null ? (appointmentTitle ?? '') : railRungLabels[mark.rung];
}

/** Everything the strip draws, said once for a screen reader. */
export function railSummary(rail: RailData, nowMinute: number): string {
    const sentences = [`It is ${clockOf(nowMinute)}.`];

    if (rail.sessionStartedMinute !== null) {
        sentences.push(`You started at ${clockOf(rail.sessionStartedMinute)}.`);
    }

    if (rail.stepSeconds !== null) {
        sentences.push(`Started now, this step is done around ${clockOf(doneMinute(nowMinute, rail.stepSeconds))}.`);
    }

    const rungs = rail.marks.filter((mark) => mark.rung !== null);
    const appointment = rail.marks.find((mark) => mark.rung === null);

    if (rungs.length > 0) {
        const said = rungs.map((mark) => `${railMarkLabel(mark, null)} at ${clockOf(mark.minute)}`).join(', ');
        sentences.push(`${said.charAt(0).toUpperCase()}${said.slice(1)}.`);
    }

    if (appointment && rail.appointmentTitle !== null) {
        sentences.push(`${rail.appointmentTitle} is at ${clockOf(appointment.minute)}.`);
    }

    return sentences.join(' ');
}

export function rungMinutesLabel(rung: PlanRung): string {
    return `Minutes to ${planRungLabels[rung]}`;
}

export function rungMinutesNote(assumed: boolean): string {
    return assumed ? 'min, assumed' : 'min, yours';
}

function estimatedCost(seconds: number | null, nowMinute: number): string | null {
    const estimate = formatEstimate(seconds);

    return seconds === null || estimate === null
        ? null
        : `About ${estimate}, so done around ${clockOf(doneMinute(nowMinute, seconds))}`;
}

const notEstimated = 'Nobody has estimated this one.';

/** What starting now costs, as a time on the clock; a missing estimate says so rather than reading as zero. */
export function estimateLine(seconds: number | null, nowMinute: number): string {
    const cost = estimatedCost(seconds, nowMinute);

    return cost === null ? notEstimated : `${cost} if you start now.`;
}

/** The same cost once the step is under way, where "if you start now" would be a step behind. */
export function underWayEstimateLine(seconds: number | null, nowMinute: number): string {
    const cost = estimatedCost(seconds, nowMinute);

    return cost === null ? notEstimated : `${cost}.`;
}

/** A step the app wrote says who wrote it, beside Start, so it is never mistaken for one they did. */
export const suggestedLabel = 'suggested step';

export function partOfLine(intentionTitle: string): string {
    return `Part of ${intentionTitle}.`;
}

export const nothingNeedsYou = 'Nothing needs you right now.';

export const homeCopy = {
    wholeAnswer: 'That is the whole answer.',
} as const;

export const homeBands = {
    why: 'Why this one?',
    comingUp: "What's coming up",
    beforeYouGo: 'Before you go',
    needsAttention: 'Needs you',
    sorted: 'What you just wrote',
} as const;

/** The next real time constraint as sentences on home, and the page where its settings live. */
export const comingUpCopy = {
    line: (title: string, inWords: string) => `${title}, ${inWords}.`,
    leaveAt: (clock: string) => `Leave at ${clock}.`,
    fromCalendar: 'From your calendar.',
    planFor: 'Plan for it',
    inferred: 'Read from what you wrote.',
    confirm: "That's right",
    change: 'Change',
    when: (inWords: string) => `${inWords.charAt(0).toUpperCase()}${inWords.slice(1)}.`,
    whenQuestion: 'When is it?',
    save: 'Save',
    noDeadline: "There's no deadline",
    back: 'Back to home',
} as const;

export const remindAfterCopy = {
    action: 'Remind me after',
    question: 'What should future you hear?',
} as const;

export const overwhelmedCopy = {
    lines: ["You've got a lot going on.", 'Ignore everything else for now.', "Let's do one thing."],
    back: 'Back to home',
} as const;

export const focusCopy = {
    start: 'Start',
    continue: 'Continue',
    done: 'Done',
    skip: 'Skip',
    pause: 'Pause',
    stop: 'Stop',
    stuck: "I'm stuck",
    distracted: 'I got distracted',
    thisStep: 'This step',
    stepAway: 'Step away',
    hints: {
        done: 'Finished it',
        skip: 'Not this one now',
        stuck: "Tell me what's in the way",
        pause: 'Back in a bit',
        distracted: 'I drifted off',
        stop: 'Done for now',
    },
    stuckQuestion: "What's blocking you?",
    stuckNoteQuestion: "What's in the way? You can leave this empty.",
    stuckNoteSend: 'Tell it',
    stoppedForNow: 'Stopped for now. It will be here later.',
    stuckMeta: 'every answer leads somewhere',
} as const;

export function aiConsentCopy(consented: boolean): { line: string; action: string } {
    return consented
        ? { line: 'A model outside this server can read what you capture.', action: 'Turn off' }
        : { line: 'Nothing you write leaves this server, so nothing you capture gets sorted.', action: 'Turn on' };
}

/** Asked once, at signup: the app sorts what you write, so it cannot work without this. */
export const registerConsentLabel = 'Send what I write to a model outside this server, so it can be sorted for me.';

/** In the order they are offered, which is part of the copy: the gentlest answers come first. */
const stuckReasons: { value: StuckReason; label: string }[] = [
    { value: 'dont_know_what_to_do', label: "I don't know what to do" },
    { value: 'too_big', label: 'This is too much' },
    { value: 'need_something', label: 'I need something' },
    { value: 'not_here', label: "I'm not in the right place for this" },
    { value: 'not_enough_information', label: "I don't have enough information" },
    { value: 'tired', label: "I'm tired" },
    { value: 'dont_want_to', label: "I don't want to do it" },
    { value: 'something_else', label: 'Something else' },
];

/** Being in the wrong place only makes sense for a step that has a place. */
export function stuckReasonsFor(place: Place | null): { value: StuckReason; label: string }[] {
    return place === null ? stuckReasons.filter((reason) => reason.value !== 'not_here') : stuckReasons;
}

/** The one-tap answer to a guess the why states out loud. */
export const notHereLabels: Record<Place, string> = {
    home: "I'm not at home",
    work: "I'm not at work",
    out: "I'm not out",
    computer: "I'm not at a computer",
};

export const waitingForResponses: { value: WaitingForResponse; label: string }[] = [
    { value: 'wait_longer', label: 'Wait longer' },
    { value: 'follow_up', label: 'Follow up' },
    { value: 'receive', label: 'Mark received' },
    { value: 'cancel', label: 'Cancel' },
];

/** An inferred commitment is asked about before it is treated as the person's own. */
export function commitmentResponses(
    inferred: boolean,
): { value: CommitmentResponse; label: string }[] {
    return inferred
        ? [
              { value: 'confirm', label: "That's mine" },
              { value: 'release', label: 'Not mine' },
          ]
        : [
              { value: 'keep', label: 'Done' },
              { value: 'release', label: 'Let it go' },
          ];
}

export const commitmentProvenanceLabels: Record<CommitmentProvenance, string> = {
    user_task: 'From something you were doing.',
    user_stated: 'You said this.',
    system_inferred: 'Read from what you wrote.',
};

/** Each is asked against when the person started, because a steady "less" is the claim worth testing. */
export const checkInQuestions: Record<CheckInTopic, string> = {
    overwhelm: 'Since you started using this, how much does everything weigh on you?',
    remembering: 'Since you started using this, how much do you have to keep in your own head?',
};

export const checkInResponses: { value: CheckInAnswer; label: string }[] = [
    { value: 'less', label: 'Less' },
    { value: 'same', label: 'About the same' },
    { value: 'more', label: 'More' },
    { value: 'not_now', label: 'Not now' },
];

export const checkInCopy = {
    band: 'A question for you',
    meta: 'Asked every two weeks, to tell whether this app is helping.',
} as const;

export function recurrenceLine(everyDays: number): string {
    return everyDays === 1 ? 'It comes back every day.' : `It comes back every ${everyDays} days.`;
}

/** The closing screen: the intention handled, and nothing asked of the person but what they want next. */
export const finishedCopy = {
    handled: (title: string) => `${title} is handled.`,
    next: 'Next, if you want:',
    leave: 'Leave it there',
    home: 'Back to home',
    comesBack: 'Does this come back?',
    every: 'Every',
    days: 'days',
    repeat: 'Repeat',
} as const;

/** Both frontends open the one box with the same words. */
export const captureCopy = {
    question: "What's on your mind?",
    description: "Write it however it comes out. Sorting it out is the app's job.",
    save: 'Save',
} as const;

export const commitmentCopy = {
    promise: "I said I'd do this",
    promised: "You said you'd do this.",
    list: "Everything you said you'd do",
    listTitle: "What you said you'd do",
    listEmpty: "Nothing is open. Anything you say you'll do lands here.",
} as const;
