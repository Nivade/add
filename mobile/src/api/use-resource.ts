import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { ApiError } from '@/api/client';

type Resource<T> = {
  data: T | null;
  loading: boolean;
  problem: string | null;
  reload: () => Promise<void>;
  replace: (data: T) => void;
};

/** Reloads whenever the screen comes forward, so a push acted on elsewhere is never stale here. */
export function useResource<T>(load: () => Promise<T>): Resource<T> {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [problem, setProblem] = useState<string | null>(null);

  const reload = useCallback(async () => {
    try {
      setData(await load());
      setProblem(null);
    } catch (error) {
      setProblem(
        error instanceof ApiError
          ? 'The server could not answer just now.'
          : 'The app could not reach the server.',
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
  const replace = useCallback((answered: T) => setData(answered), []);

  return { data, loading, problem, reload, replace };
}
