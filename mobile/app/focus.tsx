import type { ExecutionStateData, StuckReason } from '@add/shared';
import { commitmentCopy, focusCopy, returnCopy, stuckReasonsFor, underWayEstimateLine } from '@add/shared';
import { router } from 'expo-router';
import { useCallback, useRef, useState } from 'react';
import { Modal, Text, TextInput, View } from 'react-native';
import { ApiError } from '@/api/client';
import { api, type SessionControl } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Control, ControlRow } from '@/components/control-row';
import { QuietAction } from '@/components/quiet-action';
import { Meta, OneThing, Screen } from '@/components/screen';
import { Pending, StaleNote } from '@/components/resource-state';
import { nowMinute, SuggestedPill } from '@/components/suggested-pill';
import { makeStyles, useTheme } from '@/theme';

const useStyles = makeStyles(({ colors, type, space, field }) => ({
  controls: { gap: space(1.5) },
  pinned: { flex: 1, gap: space(1.5) },
  notice: { ...type.lead, color: colors.muted },
  progress: {
    paddingTop: space(2),
    gap: space(0.5),
  },
  progressLine: { ...type.small, color: colors.muted },
  note: { ...field, ...type.body, minHeight: 96, padding: space(2), textAlignVertical: 'top' },
  noteLabel: { ...type.body, color: colors.ink },
}));

export default function Focus() {
  const { token } = useSession();
  const styles = useStyles();
  const { colors } = useTheme();
  const load = useCallback(() => api.currentSession(token as string), [token]);
  const resource = useResource<ExecutionStateData | null>(load);
  const [stuckOpen, setStuckOpen] = useState(false);
  const [askingNote, setAskingNote] = useState(false);
  const [note, setNote] = useState('');
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
  const stepAway = data.returning || paused;

  /** A stuck answer that stops the session says so on home; Stop itself needs no line. */
  const take = (state: ExecutionStateData, fromStuck: boolean): void => {
    if (state.session.outcome === 'completed') {
      router.replace({ pathname: '/finished/[session]', params: { session: state.session.id } });

      return;
    }

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
    if (!step) {
      return;
    }

    await api.promoteCurrentStep(token as string, session.id, step.id);
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
      api.control(token as string, session.id, name, {
        stepId: step?.id ?? null,
        seenEventId: data.seenEventId,
      }),
    );

  const closeStuck = (): void => {
    setStuckOpen(false);
    setAskingNote(false);
    setNote('');
  };

  const reportStuck = (reason: StuckReason, said = ''): void => {
    closeStuck();
    void send(
      () => api.stuck(token as string, session.id, step?.id ?? null, reason, said),
      true,
    );
  };

  const controls = stepAway ? undefined : (
    <View style={styles.pinned}>
      <ControlRow label={focusCopy.thisStep}>
        <Control
          label={focusCopy.done}
          hint={focusCopy.hints.done}
          disabled={busy}
          onPress={() => void control('complete-step')}
        />
        <Control
          label={focusCopy.skip}
          hint={focusCopy.hints.skip}
          disabled={busy}
          onPress={() => void control('skip-step')}
        />
        <Control
          label={focusCopy.stuck}
          hint={focusCopy.hints.stuck}
          disabled={busy}
          onPress={() => setStuckOpen(true)}
        />
      </ControlRow>
      <ControlRow label={focusCopy.stepAway}>
        <Control
          label={focusCopy.pause}
          hint={focusCopy.hints.pause}
          disabled={busy}
          onPress={() => void control('pause')}
        />
        <Control
          label={focusCopy.distracted}
          hint={focusCopy.hints.distracted}
          disabled={busy}
          onPress={() => void control('distracted')}
        />
        <Control
          label={focusCopy.stop}
          hint={focusCopy.hints.stop}
          disabled={busy}
          onPress={() => void control('stop')}
        />
      </ControlRow>
    </View>
  );

  return (
    <Screen bottom={controls}>
      <StaleNote problem={problem} />
      <Meta>{intention.title}</Meta>

      {stepAway ? (
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
          {step && <Meta>{underWayEstimateLine(step.estimatedSeconds, nowMinute())}</Meta>}
          {step?.generated && <SuggestedPill />}
          {data.currentStepIsCommitment ? (
            <Meta>{commitmentCopy.promised}</Meta>
          ) : (
            <QuietAction label={commitmentCopy.promise} onPress={() => void promise()} />
          )}
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
        onRequestClose={closeStuck}
      >
        <Screen>
          <OneThing>{focusCopy.stuckQuestion}</OneThing>
          <Meta>{focusCopy.stuckMeta}</Meta>

          <View style={styles.controls}>
            {askingNote ? (
              <>
                <Text style={styles.noteLabel}>{focusCopy.stuckNoteQuestion}</Text>
                <TextInput
                  style={styles.note}
                  value={note}
                  onChangeText={setNote}
                  placeholderTextColor={colors.muted}
                  multiline
                  autoFocus
                  accessibilityLabel={focusCopy.stuckNoteQuestion}
                />
                <Button
                  label={focusCopy.stuckNoteSend}
                  tone="primary"
                  onPress={() => reportStuck('something_else', note.trim())}
                />
              </>
            ) : (
              stuckReasonsFor(step?.place ?? null).map((reason) => (
                <Button
                  key={reason.value}
                  label={reason.label}
                  onPress={() =>
                    reason.value === 'something_else'
                      ? setAskingNote(true)
                      : reportStuck(reason.value)
                  }
                />
              ))
            )}
          </View>
        </Screen>
      </Modal>
    </Screen>
  );
}
