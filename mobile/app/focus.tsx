import type { ExecutionStateData } from '@add/shared';
import { stuckReasons } from '@add/shared';
import { router } from 'expo-router';
import { useCallback, useState } from 'react';
import { Modal, StyleSheet, Text, View } from 'react-native';
import { api, type SessionControl } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Loading, Meta, OneThing, Screen } from '@/components/screen';
import { theme } from '@/theme';

export default function Focus() {
  const { token } = useSession();
  const load = useCallback(() => api.currentSession(token as string), [token]);
  const { data, loading, replace } =
    useResource<ExecutionStateData | null>(load);
  const [stuckOpen, setStuckOpen] = useState(false);

  if (loading && !data) {
    return <Loading />;
  }

  if (!data) {
    return (
      <Screen>
        <OneThing>Nothing is running.</OneThing>
        <Button label="Back" onPress={() => router.replace('/')} />
      </Screen>
    );
  }

  const { session, intention, progress, elapsed } = data;
  const step = session.currentStep;
  const paused = session.pausedAt !== null;

  const take = (state: ExecutionStateData): void => {
    if (state.session.endedAt !== null || state.session.currentStep === null) {
      router.replace('/');

      return;
    }

    replace(state);
  };

  const control = async (name: SessionControl): Promise<void> =>
    take(await api.control(token as string, session.id, name));

  return (
    <Screen>
      <Meta>{intention.title}</Meta>

      {paused ? (
        <>
          <OneThing>Welcome back.</OneThing>
          <Meta>
            you left off at {(step?.title ?? intention.title).toLowerCase()}
          </Meta>
          <Button
            label="Continue"
            tone="primary"
            onPress={() => void control('resume')}
          />
        </>
      ) : (
        <>
          <OneThing>{step?.title ?? intention.title}</OneThing>

          <View style={styles.controls}>
            <Button label="Done" onPress={() => void control('complete-step')} />
            <Button label="Skip" onPress={() => void control('skip-step')} />
            <Button label="Pause" onPress={() => void control('pause')} />
            <Button label="I'm stuck" onPress={() => setStuckOpen(true)} />
            <Button
              label="I got distracted"
              onPress={() => void control('distracted')}
            />
            <Button label="Stop" onPress={() => void control('stop')} />
          </View>
        </>
      )}

      <View style={styles.progress}>
        <Text style={styles.progressLine}>{elapsed.toLowerCase()}</Text>
        {progress.map((line) => (
          <Text key={line} style={styles.progressLine}>
            {line.toLowerCase()}
          </Text>
        ))}
      </View>

      <Modal
        visible={stuckOpen}
        animationType="slide"
        transparent={false}
        onRequestClose={() => setStuckOpen(false)}
      >
        <Screen>
          <OneThing>What's blocking you?</OneThing>
          <Meta>every answer leads somewhere</Meta>

          <View style={styles.controls}>
            {stuckReasons.map((reason) => (
              <Button
                key={reason.value}
                label={reason.label}
                onPress={() => {
                  setStuckOpen(false);
                  void api
                    .stuck(token as string, session.id, reason.value)
                    .then(take);
                }}
              />
            ))}
          </View>
        </Screen>
      </Modal>
    </Screen>
  );
}

const styles = StyleSheet.create({
  controls: { gap: theme.space(1.5) },
  progress: {
    borderTopColor: theme.color.border,
    borderTopWidth: 1,
    paddingTop: theme.space(2),
    gap: theme.space(0.5),
  },
  progressLine: { color: theme.color.muted, fontSize: 14 },
});
