export type BackwardsPlanData = {
intentionId: string,
deadlineClock: string,
rungs: PlanRungData[],
};
export type CaptureData = {
id: string,
body: string,
source: CaptureSource,
intentionId: string | null,
createdAt: string,
};
export type CaptureSource = 'text' | 'voice' | 'photo' | 'document' | 'email' | 'url';
export type ComingUpData = {
intention: IntentionData,
inWords: string,
plan: BackwardsPlanData | null,
};
export type ExecutionEventType = 'started' | 'step_completed' | 'step_skipped' | 'paused' | 'resumed' | 'stuck' | 'distracted' | 'stopped';
export type ExecutionSessionData = {
id: string,
intentionId: string,
currentStep: StepData | null,
outcome: SessionOutcome | null,
stepsCompleted: number,
startedAt: string,
pausedAt: string | null,
endedAt: string | null,
};
export type ExecutionStateData = {
session: ExecutionSessionData,
intention: IntentionData,
progress: string[],
};
export type HomeData = {
rightNow: NextActionData | null,
session: ExecutionStateData | null,
comingUp: ComingUpData | null,
needsAttention: IntentionData[],
restCount: number,
};
export type IntentionData = {
id: string,
title: string,
why: string | null,
status: IntentionStatus,
deadlineAt: string | null,
};
export type IntentionStatus = 'captured' | 'active' | 'done' | 'set_aside';
export type NextActionData = {
step: StepData,
intention: IntentionData,
why: string[],
};
export type OverwhelmedData = {
smallestStep: NextActionData | null,
restCount: number,
};
export type PlanRung = 'find_things' | 'get_ready' | 'leave';
export type PlanRungData = {
rung: PlanRung,
at: string,
clock: string,
seconds: number,
assumed: boolean,
alreadyPassed: boolean,
};
export type RailData = {
nowAt: string,
minuteOfDay: number,
sessionStartedAt: string | null,
deadlineAt: string | null,
deadlineTitle: string | null,
};
export type SessionOutcome = 'continued' | 'completed' | 'stopped';
export type StepData = {
id: string,
intentionId: string,
title: string,
position: number,
estimatedSeconds: number | null,
status: StepStatus,
skipCount: number,
};
export type StepStatus = 'pending' | 'done' | 'skipped';
export type StuckReason = 'dont_know_what_to_do' | 'too_big' | 'need_something' | 'not_enough_information' | 'tired' | 'dont_want_to' | 'something_else';
