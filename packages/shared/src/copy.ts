import type {
    CheckInAnswer,
    CheckInTopic,
    CommitmentProvenance,
    CommitmentResponse,
    Place,
    PlanRung,
    StuckReason,
    WaitingForResponse,
} from './generated';

/** The count may be stated, never enumerated, so both frontends state it the same way. */
export function restCountLine(count: number): string {
    if (count === 0) {
        return 'nothing else is waiting';
    }

    return `${count} other ${count === 1 ? 'thing' : 'things'}, none of which you need to think about`;
}

/** Said while a capture is still with the model, so it never looks lost. */
export function sortingLine(count: number): string {
    return count === 1
        ? 'Sorting the thought you just wrote down.'
        : `Sorting the ${count} thoughts you just wrote down.`;
}

/** Coming back is welcomed, never timed: both frontends say it in these words. */
export const returnCopy = {
    welcome: 'Welcome back.',
    paused: 'Paused.',
    pausedMeta: 'Continue whenever you are ready.',
    workingOn: (title: string): string => `You were working on ${title}.`,
    stepsDone: (count: number): string | null =>
        count === 0
            ? null
            : `You had done ${count} ${count === 1 ? 'step' : 'steps'}.`,
};

export const planRungLabels: Record<PlanRung, string> = {
    find_things: 'find what you need',
    get_ready: 'get ready',
    leave: 'leave',
};

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
    user_task: 'from something you were doing',
    user_stated: 'you said this',
    system_inferred: 'read from what you wrote',
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
    band: 'Looking back',
    meta: 'Asked every two weeks, to tell whether this app is helping.',
} as const;

export function recurrenceLine(everyDays: number): string {
    return everyDays === 1 ? 'repeats every day' : `repeats every ${everyDays} days`;
}

/** One question per way in, worded the same on both clients. */
export const entryCopy = {
    waitingFor: {
        question: 'Who or what are you waiting on?',
        meta: 'Nothing to do until they get back to you. This keeps it from being forgotten.',
        placeholder: 'John',
        label: 'Who or what',
        notePlaceholder: 'the contract',
        noteLabel: 'What for',
    },
    commitment: {
        question: 'What did you say you would do?',
        meta: 'Said out loud or typed, it counts the same either way.',
        placeholder: "I'll call Sarah Friday",
        label: 'What you said you would do',
    },
    futureReminder: {
        question: 'What should future you hear, and when?',
        meta: 'Say when in the same sentence.',
        placeholder: 'Tomorrow at 5, buy dishwasher tablets',
        label: 'What and when',
    },
    paste: {
        question: 'Paste something that arrived',
        meta: 'An email, a letter, a message. The app says whether it needs you.',
        placeholder: 'Your car insurance expires on 14 October.',
        label: 'What arrived',
    },
} as const;

export const commitmentCopy = {
    promise: "I said I'd do this",
    promised: "You said you'd do this.",
    list: "Everything you said you'd do",
    listTitle: "What you said you'd do",
    listEmpty: "Nothing is open. Anything you say you'll do lands here.",
} as const;
