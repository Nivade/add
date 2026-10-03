import type {
  AccessTokenData,
  AiConsentData,
  AppointmentKind,
  CaptureData,
  CaptureKind,
  CaptureSource,
  CheckInAnswer,
  CheckInTopic,
  ComingUpData,
  CommitmentData,
  CommitmentListData,
  CommitmentResponse,
  DeviceData,
  DevicePlatform,
  ExecutionStateData,
  FutureReminderData,
  HomeData,
  IntentionData,
  NextActionData,
  OverwhelmedData,
  Place,
  PlanRung,
  StuckReason,
  WaitingForData,
  WaitingForResponse,
} from '@add/shared';
import { request } from './client';

/** The controls that need no answer from the person, which is every one but "I'm stuck". */
export type SessionControl =
  | 'complete-step'
  | 'skip-step'
  | 'pause'
  | 'resume'
  | 'distracted'
  | 'stop';

export const api = {
  signIn: (email: string, password: string, deviceName: string, code?: string) =>
    request<AccessTokenData>('/tokens', {
      method: 'POST',
      body: { email, password, device_name: deviceName, code },
    }),

  signOut: (token: string) =>
    request<void>('/tokens/current', { method: 'DELETE', token }),

  registerDevice: (token: string, pushToken: string, platform: DevicePlatform) =>
    request<DeviceData>('/devices', {
      method: 'POST',
      token,
      body: { push_token: pushToken, platform },
    }),

  home: (token: string) => request<HomeData>('/home', { token }),

  appointment: (token: string, kind: AppointmentKind, id: string) =>
    request<ComingUpData | null>(`/appointments/${kind}/${id}`, { token }),

  aiConsent: (token: string) =>
    request<AiConsentData>('/ai-consent', { token }),

  updateAiConsent: (token: string, consented: boolean) =>
    request<AiConsentData>('/ai-consent', {
      method: 'PATCH',
      token,
      body: { consented },
    }),

  nextAction: (token: string) =>
    request<NextActionData | null>('/next-action', { token }),

  overwhelmed: (token: string) =>
    request<OverwhelmedData>('/overwhelmed', { token }),

  capture: (token: string, body: string, source: CaptureSource = 'text') =>
    request<CaptureData>('/captures', {
      method: 'POST',
      token,
      body: { body, source },
    }),

  confirmCaptureKind: (token: string, captureId: string) =>
    request<CaptureData>(`/captures/${captureId}/confirm`, {
      method: 'POST',
      token,
    }),

  changeCaptureKind: (token: string, captureId: string, kind: CaptureKind) =>
    request<CaptureData>(`/captures/${captureId}/kind`, {
      method: 'POST',
      token,
      body: { kind },
    }),

  adjustPlan: (
    token: string,
    kind: AppointmentKind,
    id: string,
    minutes: Partial<Record<PlanRung, number>>,
  ) =>
    request<void>(
      `${kind === 'calendar_event' ? '/calendar-events' : '/intentions'}/${id}/plan`,
      { method: 'PATCH', token, body: minutes },
    ),

  clarify: (token: string, intentionId: string, answer: string) =>
    request<IntentionData>(`/intentions/${intentionId}/clarification`, {
      method: 'PATCH',
      token,
      body: { answer },
    }),

  dismissReminder: (token: string, id: string) =>
    request<void>(`/reminders/${id}/dismiss`, { method: 'POST', token }),

  currentSession: (token: string) =>
    request<ExecutionStateData | null>('/sessions/current', { token }),

  startSession: (token: string, stepId: string) =>
    request<ExecutionStateData>('/sessions', {
      method: 'POST',
      token,
      body: { step_id: stepId },
    }),

  /** A step control names the step it acts on; a session control names the event it was tapped against. */
  control: (
    token: string,
    sessionId: string,
    control: SessionControl,
    seen: { stepId: string | null; seenEventId: string },
  ) =>
    request<ExecutionStateData>(`/sessions/${sessionId}/${control}`, {
      method: 'POST',
      token,
      body:
        control === 'complete-step' || control === 'skip-step'
          ? { step_id: seen.stepId }
          : { seen_event_id: seen.seenEventId },
    }),

  stuck: (
    token: string,
    sessionId: string,
    stepId: string | null,
    reason: StuckReason,
  ) =>
    request<ExecutionStateData>(`/sessions/${sessionId}/stuck`, {
      method: 'POST',
      token,
      body: { step_id: stepId, reason },
    }),

  respondToWaitingFor: (
    token: string,
    waitingForId: string,
    response: WaitingForResponse,
  ) =>
    request<WaitingForData>(`/waiting-fors/${waitingForId}/respond`, {
      method: 'POST',
      token,
      body: { response },
    }),

  checkIn: (token: string, topic: CheckInTopic, response: CheckInAnswer) =>
    request<void>(`/check-ins/${topic}`, {
      method: 'POST',
      token,
      body: { response },
    }),

  commitments: (token: string) =>
    request<CommitmentListData>('/commitments', { token }),

  promoteToCommitment: (token: string, intentionId: string) =>
    request<CommitmentData>(`/intentions/${intentionId}/commitment`, {
      method: 'POST',
      token,
    }),

  notHere: (token: string, place: Place) =>
    request<void>('/whereabouts/not-here', {
      method: 'POST',
      token,
      body: { place },
    }),

  promoteCurrentStep: (token: string, sessionId: string, stepId: string) =>
    request<CommitmentData>(`/sessions/${sessionId}/commitment`, {
      method: 'POST',
      token,
      body: { step_id: stepId },
    }),

  respondToCommitment: (
    token: string,
    commitmentId: string,
    response: CommitmentResponse,
  ) =>
    request<CommitmentData>(`/commitments/${commitmentId}/respond`, {
      method: 'POST',
      token,
      body: { response },
    }),

  repeatIntention: (token: string, intentionId: string, everyDays: number) =>
    request<IntentionData>(`/intentions/${intentionId}/recurrence`, {
      method: 'POST',
      token,
      body: { every_days: everyDays },
    }),

  remindAfterEvent: (
    token: string,
    calendarEventId: string,
    message: string,
    offsetMinutes: number,
  ) =>
    request<FutureReminderData>(
      `/calendar-events/${calendarEventId}/future-reminder`,
      {
        method: 'POST',
        token,
        body: { message, offset_minutes: offsetMinutes },
      },
    ),

};
