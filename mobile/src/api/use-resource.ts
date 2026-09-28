import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { ApiError, UNREACHABLE } from '@/api/client';

export type Resource<T> = {
  reload: () => Promise<void>;
  replace: (data: T) => void;
} & (
  | { status: 'loading' }
  | { status: 'unreachable'; problem: string }
  | { status: 'ready'; data: T; problem: string | null }
);

/** Reloads whenever the screen comes forward, so a push acted on elsewhere is never stale here. */
export function useResource<T>(load: () => Promise<T>): Resource<T> {
  const [loaded, setLoaded] = useState<{ data: T } | null>(null);
  const [loading, setLoading] = useState(true);
  const [problem, setProblem] = useState<string | null>(null);

  const reload = useCallback(async () => {
    try {
      setLoaded({ data: await load() });
      setProblem(null);
    } catch (error) {
      setProblem(
        error instanceof ApiError ? 'The server could not answer just now.' : UNREACHABLE,
      );
    } finally {
      setLoading(false);
    }
  }, [load]);

  useFocusEffect(
    useCallback(() => {
      void reload();
    }, [reload]),
  );

  /** A write already answered with the new state, so taking it here saves reading it back. */
  const replace = useCallback((answered: T) => setLoaded({ data: answered }), []);

  if (loaded) {
    return { status: 'ready', data: loaded.data, problem, reload, replace };
  }

  if (loading || problem === null) {
    return { status: 'loading', reload, replace };
  }

  return { status: 'unreachable', problem, reload, replace };
}
