import type {
  AccessTokenData,
  AppointmentKind,
  CaptureData,
  CaptureSource,
  DeviceData,
  DevicePlatform,
  ExecutionStateData,
  HomeData,
  IntentionData,
  NextActionData,
  OverwhelmedData,
  PlanRung,
  StuckReason,
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

  control: (token: string, sessionId: string, control: SessionControl) =>
    request<ExecutionStateData>(`/sessions/${sessionId}/${control}`, {
      method: 'POST',
      token,
    }),

  stuck: (token: string, sessionId: string, reason: StuckReason) =>
    request<ExecutionStateData>(`/sessions/${sessionId}/stuck`, {
      method: 'POST',
      token,
      body: { reason },
    }),
};
