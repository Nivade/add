import Constants from 'expo-constants';

export class ApiError extends Error {
  constructor(
    readonly status: number,
    readonly errors: Record<string, string[]> = {},
  ) {
    super(`The API answered ${status}.`);
  }

  /** The first message the server gave, which is already phrased for a person. */
  firstMessage(fallback: string): string {
    return Object.values(this.errors)[0]?.[0] ?? fallback;
  }
}

export const UNREACHABLE = 'The app could not reach the server.';

/** Every form words a write that did not land the same way. */
export function writeProblem(error: unknown): string {
  return error instanceof ApiError
    ? error.firstMessage('That did not go through. Try again.')
    : UNREACHABLE;
}

let onUnauthorized: ((token: string) => void) | null = null;

/** The session registers this, so a token the server stopped accepting signs the phone out from any screen. */
export function whenUnauthorized(handler: ((token: string) => void) | null): void {
  onUnauthorized = handler;
}

function baseUrl(): string {
  const url = Constants.expoConfig?.extra?.apiUrl;

  if (typeof url !== 'string') {
    throw new Error('No apiUrl in the Expo config.');
  }

  return url.replace(/\/$/, '');
}

type Options = {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
  body?: unknown;
  token?: string | null;
};

export async function request<T>(
  path: string,
  { method = 'GET', body, token }: Options = {},
): Promise<T> {
  const response = await fetch(`${baseUrl()}/api/v1${path}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: body === undefined ? undefined : JSON.stringify(body),
  });

  if (response.status === 401 && token) {
    onUnauthorized?.(token);
  }

  if (!response.ok) {
    const payload = await response.json().catch(() => ({}));

    throw new ApiError(response.status, payload?.errors ?? {});
  }

  if (response.status === 204) {
    return undefined as T;
  }

  return (await response.json()) as T;
}
