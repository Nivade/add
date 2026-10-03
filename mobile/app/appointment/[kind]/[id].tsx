import type { AppointmentKind, ComingUpData, PlanRung } from '@add/shared';
import {
  planRungLabels,
  remindAfterCopy,
  rungMinutesLabel,
  rungMinutesNote,
} from '@add/shared';
import { router, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { Text, TextInput, View } from 'react-native';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Meta, OneThing, Screen } from '@/components/screen';
import { Pending, StaleNote } from '@/components/resource-state';
import { makeStyles, useTheme } from '@/theme';

const useStyles = makeStyles(({ colors, type, field, space }) => ({
  after: { gap: space(1), marginTop: space(2) },
  afterLabel: { ...type.small, color: colors.muted, flex: 1 },
  message: field,
  rung: { flexDirection: 'row', alignItems: 'center', gap: space(1.5) },
  clock: { ...type.numeric, color: colors.ink, width: 56 },
  passed: { color: colors.muted, textDecorationLine: 'line-through' },
  rungLabel: { ...type.body, color: colors.muted, flex: 1 },
  minutes: {
    ...field,
    ...type.numeric,
    width: 72,
    paddingHorizontal: space(1),
    textAlign: 'right',
  },
  assumed: { ...type.small, color: colors.muted, width: 84 },
}));

/** Where a reminder lands. Every number here is the person's to overrule, resolved by id so a stale deep link never trusts what home shows next. */
export default function Appointment() {
  const { kind, id } = useLocalSearchParams<{ kind: AppointmentKind; id: string }>();
  const { token } = useSession();
  const styles = useStyles();
  const { colors } = useTheme();
  const load = useCallback(
    () => api.appointment(token as string, kind, id),
    [token, kind, id],
  );
  const resource = useResource<ComingUpData | null>(load);

  const [minutes, setMinutes] = useState<Partial<Record<PlanRung, string>>>({});
  const [saving, setSaving] = useState(false);
  const [afterMessage, setAfterMessage] = useState('');
  const [afterMinutes, setAfterMinutes] = useState('30');
  const [remindSaved, setRemindSaved] = useState(false);

  if (resource.status !== 'ready') {
    return <Pending resource={resource} />;
  }

  const { data: appointment, problem } = resource;

  if (!appointment) {
    return (
      <Screen>
        <OneThing>That one is not what is next any more.</OneThing>
        <Button label="Back" onPress={() => router.replace('/')} />
      </Screen>
    );
  }

  const save = async () => {
    setSaving(true);

    try {
      await api.adjustPlan(
        token as string,
        appointment.kind,
        appointment.id,
        Object.fromEntries(
          Object.entries(minutes).map(([rung, stated]) => [
            rung,
            Number(stated),
          ]),
        ),
      );
      router.replace('/');
    } finally {
      setSaving(false);
    }
  };

  const remindAfter = async () => {
    const message = afterMessage.trim();

    if (message === '') {
      return;
    }

    await api.remindAfterEvent(
      token as string,
      appointment.id,
      message,
      Number(afterMinutes) || 0,
    );
    setAfterMessage('');
    setRemindSaved(true);
  };

  return (
    <Screen>
      <StaleNote problem={problem} />
      <OneThing>{appointment.title}</OneThing>
      <Meta>{appointment.inWords}</Meta>

      {appointment.plan?.rungs.map((rung) => (
        <View key={rung.rung} style={styles.rung}>
          <Text
            style={[styles.clock, rung.alreadyPassed && styles.passed]}
            accessibilityLabel={`${planRungLabels[rung.rung]} at ${rung.clock}`}
          >
            {rung.clock}
          </Text>
          <Text style={styles.rungLabel}>{planRungLabels[rung.rung]}</Text>
          <TextInput
            style={styles.minutes}
            defaultValue={String(Math.round(rung.seconds / 60))}
            onChangeText={(text) =>
              setMinutes((stated) => ({ ...stated, [rung.rung]: text }))
            }
            keyboardType="number-pad"
            inputMode="numeric"
            accessibilityLabel={rungMinutesLabel(rung.rung)}
          />
          <Text style={styles.assumed}>
            {rungMinutesNote(rung.assumed)}
          </Text>
        </View>
      ))}

      {appointment.plan && (
        <Button
          label={saving ? 'Saving' : 'Save'}
          tone="primary"
          disabled={saving}
          onPress={() => void save()}
        />
      )}

      {appointment.kind === 'calendar_event' && (
        <View style={styles.after}>
          <TextInput
            style={styles.message}
            value={afterMessage}
            onChangeText={(text) => {
              setAfterMessage(text);
              setRemindSaved(false);
            }}
            placeholder={remindAfterCopy.question}
            placeholderTextColor={colors.muted}
            accessibilityLabel={remindAfterCopy.question}
          />
          <View style={styles.rung}>
            <TextInput
              style={styles.minutes}
              value={afterMinutes}
              onChangeText={setAfterMinutes}
              keyboardType="number-pad"
              inputMode="numeric"
              accessibilityLabel="Minutes after it starts"
            />
            <Text style={styles.afterLabel}>min after it starts</Text>
          </View>
          {remindSaved && <Meta>future you will hear it then</Meta>}
          <Button label={remindAfterCopy.action} onPress={() => void remindAfter()} />
        </View>
      )}

      <Button label="Back" onPress={() => router.replace('/')} />
    </Screen>
  );
}
