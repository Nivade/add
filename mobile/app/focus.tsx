import type { ExecutionStateData } from '@add/shared';
import { commitmentCopy, focusCopy, returnCopy, stepMeta, stuckReasonsFor } from '@add/shared';
import { router } from 'expo-router';
import { useCallback, useState } from 'react';
import { Modal, StyleSheet, Text, View } from 'react-native';
import { ApiError } from '@/api/client';
import { api, type SessionControl } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { QuietAction } from '@/components/quiet-action';
import { Meta, OneThing, Screen } from '@/components/screen';
import { Pending, StaleNote } from '@/components/resource-state';
import { theme } from '@/theme';

export default function Focus() {
  const { token } = useSession();
  const load = useCallback(() => api.currentSession(token as string), [token]);
  const resource = useResource<ExecutionStateData | null>(load);
  const [stuckOpen, setStuckOpen] = useState(false);
  const [busy, setBusy] = useState(false);

  if (resource.status !== 'ready') {
    return <Pending resource={resource} />;
  }

  const { data, problem, replace, reload } = resource;

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

  const promise = async (): Promise<void> => {
    await api.promoteCurrentStep(token as string, session.id);
    await reload();
  };

  /** A 409 means another tap already moved the session on, so the screen catches up instead. */
  const send = async (
    write: () => Promise<ExecutionStateData>,
  ): Promise<void> => {
    setBusy(true);

    try {
      take(await write());
    } catch (error) {
      if (!(error instanceof ApiError && error.status === 409)) {
        throw error;
      }

      await reload();
    } finally {
      setBusy(false);
    }
  };

  const control = (name: SessionControl): Promise<void> =>
    send(() =>
      api.control(token as string, session.id, name, step?.id ?? null),
    );

  return (
    <Screen>
      <StaleNote problem={problem} />
      <Meta>{intention.title}</Meta>

      {data.returning || paused ? (
        <>
          <OneThing>
            {data.returning ? returnCopy.welcome : returnCopy.paused}
          </OneThing>
          <Meta>
            {data.returning
              ? returnCopy.meta(intention.title, data.stepsDone)
              : returnCopy.pausedMeta}
          </Meta>
          <Button
            label="Continue"
            tone="primary"
            disabled={busy}
            onPress={() => void control('resume')}
          />
        </>
      ) : (
        <>
          <OneThing>{step?.title ?? intention.title}</OneThing>
          {step && <Meta>{stepMeta(step)}</Meta>}
          {data.currentStepIsCommitment ? (
            <Meta>{commitmentCopy.promised}</Meta>
          ) : (
            <QuietAction label={commitmentCopy.promise} onPress={() => void promise()} />
          )}

          <View style={styles.controls}>
            <Button
              label={focusCopy.done}
              disabled={busy}
              onPress={() => void control('complete-step')}
            />
            <Button
              label={focusCopy.skip}
              disabled={busy}
              onPress={() => void control('skip-step')}
            />
            <Button
              label={focusCopy.pause}
              disabled={busy}
              onPress={() => void control('pause')}
            />
            <Button
              label={focusCopy.stuck}
              disabled={busy}
              onPress={() => setStuckOpen(true)}
            />
            <Button
              label={focusCopy.distracted}
              disabled={busy}
              onPress={() => void control('distracted')}
            />
            <Button
              label={focusCopy.stop}
              disabled={busy}
              onPress={() => void control('stop')}
            />
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
          <OneThing>{focusCopy.stuckQuestion}</OneThing>
          <Meta>{focusCopy.stuckMeta}</Meta>

          <View style={styles.controls}>
            {stuckReasonsFor(step?.place ?? null).map((reason) => (
              <Button
                key={reason.value}
                label={reason.label}
                onPress={() => {
                  setStuckOpen(false);
                  void send(() =>
                    api.stuck(
                      token as string,
                      session.id,
                      step?.id ?? null,
                      reason.value,
                    ),
                  );
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
