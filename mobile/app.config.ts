import type { ConfigContext, ExpoConfig } from 'expo/config';

/** The device is not the host, so a simulator and a phone each need a different address for the same Sail. */
export default ({ config }: ConfigContext): ExpoConfig => ({
  ...(config as ExpoConfig),
  extra: {
    ...config.extra,
    apiUrl: process.env.EXPO_PUBLIC_API_URL ?? 'https://add.nvade.dev',
  },
});
