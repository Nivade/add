import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';

type Resource<T> = {
  data: T | null;
  loading: boolean;
  failed: boolean;
  reload: () => Promise<void>;
  replace: (data: T) => void;
};

/** Reloads whenever the screen comes forward, so a push acted on elsewhere is never stale here. */
export function useResource<T>(load: () => Promise<T>): Resource<T> {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [failed, setFailed] = useState(false);

  const reload = useCallback(async () => {
    try {
      setData(await load());
      setFailed(false);
    } catch {
      setFailed(true);
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

  return { data, loading, failed, reload, replace };
}
