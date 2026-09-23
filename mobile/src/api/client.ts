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

  if (!response.ok) {
    const payload = await response.json().catch(() => ({}));

    throw new ApiError(response.status, payload?.errors ?? {});
  }

  if (response.status === 204) {
    return undefined as T;
  }

  return (await response.json()) as T;
}
