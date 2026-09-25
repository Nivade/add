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

/**
 * A refused permission is an answer, not an error: the reminder still lands on home.
 *
 * `Device.isDevice` is false on both an iOS simulator and an Android emulator, but only the
 * simulator lacks the APNs sandbox a real token needs — an Android emulator with Play Services
 * gets a genuine Expo push token over FCM.
 */
export async function registerForPush(token: string): Promise<void> {
  if (!Device.isDevice && Platform.OS === 'ios') {
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
  ).catch(() => null);

  if (pushToken === null) {
    return;
  }

  await api
    .registerDevice(token, pushToken.data, Platform.OS === 'ios' ? 'ios' : 'android')
    .catch(() => undefined);
}
