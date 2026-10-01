import type {
  AccessTokenData,
  AiConsentData,
  AppointmentKind,
  CaptureData,
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

  control: (
    token: string,
    sessionId: string,
    control: SessionControl,
    stepId: string | null,
  ) =>
    request<ExecutionStateData>(`/sessions/${sessionId}/${control}`, {
      method: 'POST',
      token,
      body: { step_id: stepId },
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

  promoteCurrentStep: (token: string, sessionId: string) =>
    request<CommitmentData>(`/sessions/${sessionId}/commitment`, {
      method: 'POST',
      token,
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
