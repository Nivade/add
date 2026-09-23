import Constants from 'expo-constants';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';
import { api } from '@/api/endpoints';

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowBanner: true,
    shouldShowList: true,
    shouldPlaySound: false,
    shouldSetBadge: false,
  }),
});

/** A refused permission is an answer, not an error: the reminder still lands on home. */
export async function registerForPush(token: string): Promise<void> {
  if (!Device.isDevice) {
    return;
  }

  const existing = await Notifications.getPermissionsAsync();
  const granted =
    existing.granted ||
    (await Notifications.requestPermissionsAsync()).granted;

  if (!granted) {
    return;
  }

  const projectId = Constants.expoConfig?.extra?.eas?.projectId;

  const pushToken = await Notifications.getExpoPushTokenAsync(
    projectId ? { projectId } : undefined,
  );

  await api
    .registerDevice(token, pushToken.data, Platform.OS === 'ios' ? 'ios' : 'android')
    .catch(() => undefined);
}
