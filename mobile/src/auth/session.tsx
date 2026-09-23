import * as Device from 'expo-device';
import * as SecureStore from 'expo-secure-store';
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { api } from '@/api/endpoints';
import { registerForPush } from '@/push/register-for-push';

const KEY = 'access-token';

type Session = {
  token: string | null;
  loading: boolean;
  signIn: (email: string, password: string, code?: string) => Promise<void>;
  signOut: () => Promise<void>;
};

const SessionContext = createContext<Session | null>(null);

export function SessionProvider({ children }: { children: ReactNode }) {
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    SecureStore.getItemAsync(KEY)
      .then(setToken)
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (token) {
      void registerForPush(token);
    }
  }, [token]);

  const signIn = useCallback(
    async (email: string, password: string, code?: string) => {
      const { token: issued } = await api.signIn(
        email,
        password,
        deviceName(),
        code,
      );

      await SecureStore.setItemAsync(KEY, issued);
      setToken(issued);
    },
    [],
  );

  const signOut = useCallback(async () => {
    const current = token;

    setToken(null);
    await SecureStore.deleteItemAsync(KEY);

    if (current) {
      await api.signOut(current).catch(() => undefined);
    }
  }, [token]);

  const value = useMemo(
    () => ({ token, loading, signIn, signOut }),
    [token, loading, signIn, signOut],
  );

  return (
    <SessionContext.Provider value={value}>{children}</SessionContext.Provider>
  );
}

export function useSession(): Session {
  const session = useContext(SessionContext);

  if (!session) {
    throw new Error('useSession is outside a SessionProvider.');
  }

  return session;
}

/** Names the token after the device, so revoking one is a recognisable choice. */
function deviceName(): string {
  return Device.deviceName ?? 'A phone';
}
