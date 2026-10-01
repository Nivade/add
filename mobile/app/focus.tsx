import type { ExecutionStateData } from '@add/shared';
import { commitmentCopy, estimateLine, focusCopy, returnCopy, stuckReasonsFor } from '@add/shared';
import { router } from 'expo-router';
import { useCallback, useRef, useState } from 'react';
import { Modal, StyleSheet, Text, View } from 'react-native';
import { ApiError } from '@/api/client';
import { api, type SessionControl } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { QuietAction } from '@/components/quiet-action';
import { Meta, OneThing, Screen } from '@/components/screen';
import { Pending, StaleNote } from '@/components/resource-state';
import { nowMinute, SuggestedPill } from '@/components/suggested-pill';
import { theme } from '@/theme';

export default function Focus() {
  const { token } = useSession();
  const load = useCallback(() => api.currentSession(token as string), [token]);
  const resource = useResource<ExecutionStateData | null>(load);
  const [stuckOpen, setStuckOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const inFlight = useRef(false);

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

  /** A stuck answer that stops the session says so on home; Stop itself needs no line. */
  const take = (state: ExecutionStateData, fromStuck: boolean): void => {
    if (state.session.endedAt !== null || state.session.currentStep === null) {
      router.replace(
        fromStuck && state.session.outcome === 'stopped'
          ? { pathname: '/', params: { notice: focusCopy.stoppedForNow } }
          : '/',
      );

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
    fromStuck = false,
  ): Promise<void> => {
    if (inFlight.current) {
      return;
    }

    inFlight.current = true;
    setBusy(true);

    try {
      take(await write(), fromStuck);
    } catch (error) {
      if (!(error instanceof ApiError && error.status === 409)) {
        throw error;
      }

      await reload();
    } finally {
      inFlight.current = false;
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
            label={focusCopy.continue}
            tone="primary"
            disabled={busy}
            onPress={() => void control('resume')}
          />
        </>
      ) : (
        <>
          {data.notice && (
            <Text accessibilityLiveRegion="polite" style={styles.notice}>
              {data.notice}
            </Text>
          )}
          <OneThing>{step?.title ?? intention.title}</OneThing>
          {step && <Meta>{estimateLine(step.estimatedSeconds, nowMinute())}</Meta>}
          {step?.generated && <SuggestedPill />}
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
        <Text style={styles.progressLine}>{elapsed}</Text>
        {progress.map((line) => (
          <Text key={line} style={styles.progressLine}>
            {line}
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
                  void send(
                    () =>
                      api.stuck(
                        token as string,
                        session.id,
                        step?.id ?? null,
                        reason.value,
                      ),
                    true,
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
  notice: { color: theme.color.muted, fontSize: 18 },
  progress: {
    borderTopColor: theme.color.border,
    borderTopWidth: 1,
    paddingTop: theme.space(2),
    gap: theme.space(0.5),
  },
  progressLine: { color: theme.color.muted, fontSize: 14 },
});
