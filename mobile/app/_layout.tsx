import * as Notifications from 'expo-notifications';
import { Stack, router, useRootNavigationState, useSegments } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { SessionProvider, useSession } from '@/auth/session';
import { startErrorReporting, withErrorReporting } from '@/errors/reporting';
import { theme } from '@/theme';

startErrorReporting();

function RootLayout() {
  return (
    <SafeAreaProvider>
      <SessionProvider>
        <StatusBar style="light" />
        <Gate />
      </SessionProvider>
    </SafeAreaProvider>
  );
}

export default withErrorReporting(RootLayout);

function Gate() {
  const { token, loading } = useSession();
  const segments = useSegments();
  const navigationReady = useRootNavigationState()?.key !== undefined;

  useEffect(() => {
    if (loading || !navigationReady) {
      return;
    }

    const signingIn = segments[0] === 'sign-in';

    if (!token && !signingIn) {
      router.replace('/sign-in');
    }

    if (token && signingIn) {
      router.replace('/');
    }
  }, [token, loading, navigationReady, segments]);

  useReminderTaps();

  return (
    <Stack
      screenOptions={{
        headerShown: false,
        contentStyle: { backgroundColor: theme.color.background },
        animation: 'fade',
      }}
    />
  );
}

/** Opening a reminder lands on the appointment it prepared for, not on a list. */
function useReminderTaps() {
  useEffect(() => {
    const open = (data: Record<string, unknown> | undefined) => {
      const kind = data?.kind;
      const id = data?.appointment_id;

      if (typeof kind === 'string' && typeof id === 'string') {
        router.push(`/appointment/${kind}/${id}`);
      }
    };

    void Notifications.getLastNotificationResponseAsync().then((response) =>
      open(response?.notification.request.content.data),
    );

    const subscription = Notifications.addNotificationResponseReceivedListener(
      (response) => open(response.notification.request.content.data),
    );

    return () => subscription.remove();
  }, []);
}
