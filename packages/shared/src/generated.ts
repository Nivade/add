export type AccessTokenData = {
token: string,
deviceName: string,
};
export type AiConsentData = {
consented: boolean,
};
export type AppointmentKind = 'intention' | 'calendar_event';
export type BackwardsPlanData = {
kind: AppointmentKind,
appointmentId: string,
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
kind: AppointmentKind,
id: string,
title: string,
inWords: string,
inferred: boolean,
plan: BackwardsPlanData | null,
};
export type CommitmentData = {
id: string,
description: string,
provenance: CommitmentProvenance,
confirmedAt: string | null,
};
export type CommitmentProvenance = 'user_task' | 'user_stated' | 'system_inferred';
export type DeviceData = {
id: string,
platform: DevicePlatform,
};
export type DevicePlatform = 'ios' | 'android';
export type ExecutionEventType = 'started' | 'step_completed' | 'step_skipped' | 'step_split' | 'paused' | 'resumed' | 'stuck' | 'distracted' | 'stopped';
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
elapsed: string,
};
export type FutureReminderData = {
id: string,
message: string,
triggerAt: string | null,
calendarEventId: string | null,
offsetSeconds: number | null,
};
export type HomeData = {
rightNow: NextActionData | null,
session: ExecutionStateData | null,
comingUp: ComingUpData | null,
reminder: ReminderData | null,
needsAttention: NeedsAttentionData[],
restCount: number,
};
export type IntentionData = {
id: string,
title: string,
why: string | null,
status: IntentionStatus,
deadlineAt: string | null,
deadlineInferred: boolean,
clarifyingQuestion: string | null,
};
export type IntentionStatus = 'captured' | 'active' | 'done' | 'set_aside';
export type NeedsAttentionData = {
kind: NeedsAttentionKind,
id: string,
title: string,
detail: string | null,
clarifyingQuestion: string | null,
};
export type NeedsAttentionKind = 'intention' | 'waiting_for';
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
nowMinute: number,
sessionStartedMinute: number | null,
leaveByMinute: number | null,
leaveByClock: string | null,
appointmentTitle: string | null,
};
export type ReminderData = {
id: string,
title: string,
lines: string[],
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
generated: boolean,
};
export type StepStatus = 'pending' | 'done' | 'skipped';
export type StuckReason = 'dont_know_what_to_do' | 'too_big' | 'need_something' | 'not_enough_information' | 'tired' | 'dont_want_to' | 'something_else';
export type WaitingForData = {
id: string,
subject: string,
note: string | null,
status: WaitingForStatus,
};
export type WaitingForResponse = 'wait_longer' | 'follow_up' | 'cancel' | 'receive';
export type WaitingForStatus = 'waiting' | 'followed_up' | 'cancelled' | 'received';
