import * as Sentry from '@sentry/react-native';
import Constants from 'expo-constants';

/** Bugsink speaks the Sentry protocol, so the DSN is the only thing that differs from hosted Sentry. */
export function startErrorReporting(): void {
  const dsn = Constants.expoConfig?.extra?.sentryDsn;

  if (typeof dsn !== 'string' || dsn === '') {
    return;
  }

  Sentry.init({
    dsn,
    // Bugsink stores what it is sent; a capture's text is the person's own words.
    sendDefaultPii: false,
    // Bugsink takes errors, not traces.
    tracesSampleRate: 0,
    enableAutoPerformanceTracing: false,
  });
}

/** Catches render errors the JS handler never sees; a no-op when init() bailed. */
export const withErrorReporting = Sentry.wrap;
