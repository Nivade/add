import type { FinishedData } from '@add/shared';
import { finishedCopy, focusCopy, recurrenceLine } from '@add/shared';
import * as Haptics from 'expo-haptics';
import { router, useLocalSearchParams } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import { Text, TextInput, View } from 'react-native';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { QuietAction } from '@/components/quiet-action';
import { Band, Meta, OneThing, Screen } from '@/components/screen';
import { Pending, StaleNote } from '@/components/resource-state';
import { makeStyles } from '@/theme';

const useStyles = makeStyles(({ colors, type, space, field }) => ({
  lines: { gap: space(0.5) },
  line: { ...type.lead, color: colors.muted },
  next: { ...type.lead, fontFamily: type.controlLabel.fontFamily, color: colors.ink },
  actions: { gap: space(1.5) },
  repeat: { gap: space(1.5) },
  repeatRow: { flexDirection: 'row', alignItems: 'center', gap: space(1.5) },
  repeatLabel: { ...type.body, color: colors.ink },
  days: { ...field, width: 96 },
}));

function Recurrence({
  intentionId,
  everyDays,
  onSaved,
}: {
  intentionId: string;
  everyDays: number | null;
  onSaved: () => Promise<void>;
}) {
  const { token } = useSession();
  const styles = useStyles();
  const [asking, setAsking] = useState(false);
  const [days, setDays] = useState('7');

  if (everyDays) {
    return <Meta>{recurrenceLine(everyDays)}</Meta>;
  }

  if (!asking) {
    return <QuietAction label={finishedCopy.comesBack} onPress={() => setAsking(true)} />;
  }

  const repeat = async (): Promise<void> => {
    const every = Number.parseInt(days, 10);

    if (Number.isNaN(every) || every < 1 || every > 365) {
      return;
    }

    await api.repeatIntention(token as string, intentionId, every);
    await onSaved();
  };

  return (
    <View style={styles.repeat}>
      <View style={styles.repeatRow}>
        <Text style={styles.repeatLabel}>{finishedCopy.every}</Text>
        <TextInput
          style={styles.days}
          value={days}
          onChangeText={setDays}
          keyboardType="number-pad"
          maxLength={3}
          accessibilityLabel={`${finishedCopy.every} … ${finishedCopy.days}`}
        />
        <Text style={styles.repeatLabel}>{finishedCopy.days}</Text>
      </View>
      <Button label={finishedCopy.repeat} onPress={() => void repeat()} />
    </View>
  );
}

function Closing({ finished, reload }: { finished: FinishedData; reload: () => Promise<void> }) {
  const { token } = useSession();
  const styles = useStyles();
  const { intention, lines, next, recurrenceEveryDays } = finished;

  const startNext = async (): Promise<void> => {
    if (!next) {
      return;
    }

    await api.startSession(token as string, next.step.id);
    router.replace('/focus');
  };

  return (
    <>
      <OneThing>{finishedCopy.handled(intention.title)}</OneThing>
      <View style={styles.lines}>
        {lines.map((line) => (
          <Text key={line} style={styles.line}>
            {line}
          </Text>
        ))}
      </View>

      {next ? (
        <Band label={finishedCopy.next}>
          <Text style={styles.next}>{next.step.title}</Text>
          {next.why[0] && <Meta>{next.why[0]}</Meta>}
          <View style={styles.actions}>
            <Button label={focusCopy.start} tone="primary" onPress={() => void startNext()} />
            <Button label={finishedCopy.leave} onPress={() => router.replace('/')} />
          </View>
        </Band>
      ) : (
        <Button label={finishedCopy.home} onPress={() => router.replace('/')} />
      )}

      <Recurrence
        intentionId={intention.id}
        everyDays={recurrenceEveryDays}
        onSaved={reload}
      />
    </>
  );
}

export default function Finished() {
  const { token } = useSession();
  const { session } = useLocalSearchParams<{ session: string }>();
  const load = useCallback(() => api.finished(token as string, session), [token, session]);
  const resource = useResource<FinishedData>(load);

  useEffect(() => {
    void Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
  }, []);

  if (resource.status !== 'ready') {
    return <Pending resource={resource} />;
  }

  return (
    <Screen>
      <StaleNote problem={resource.problem} />
      <Closing finished={resource.data} reload={resource.reload} />
    </Screen>
  );
}
