import type {
    CommitmentProvenance,
    CommitmentResponse,
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

export const planRungLabels: Record<PlanRung, string> = {
    find_things: 'find what you need',
    get_ready: 'get ready',
    leave: 'leave',
};

/** In the order they are offered, which is part of the copy: the gentlest answers come first. */
export const stuckReasons: { value: StuckReason; label: string }[] = [
    { value: 'dont_know_what_to_do', label: "I don't know what to do" },
    { value: 'too_big', label: 'This is too much' },
    { value: 'need_something', label: 'I need something' },
    { value: 'not_enough_information', label: "I don't have enough information" },
    { value: 'tired', label: "I'm tired" },
    { value: 'dont_want_to', label: "I don't want to do it" },
    { value: 'something_else', label: 'Something else' },
];

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

export function recurrenceLine(everyDays: number): string {
    return everyDays === 1 ? 'repeats every day' : `repeats every ${everyDays} days`;
}
