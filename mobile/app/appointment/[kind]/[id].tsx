import type { AppointmentKind, ComingUpData, PlanRung } from '@add/shared';
import { planRungLabels } from '@add/shared';
import { router, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { StyleSheet, Text, TextInput, View } from 'react-native';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Loading, Meta, OneThing, Screen } from '@/components/screen';
import { StaleNote, Unreachable } from '@/components/unreachable';
import { field, TOUCH_TARGET, theme } from '@/theme';

/** Where a reminder lands. Every number here is the person's to overrule, resolved by id so a stale deep link never trusts what home shows next. */
export default function Appointment() {
  const { kind, id } = useLocalSearchParams<{ kind: AppointmentKind; id: string }>();
  const { token } = useSession();
  const load = useCallback(
    () => api.appointment(token as string, kind, id),
    [token, kind, id],
  );
  const { data: appointment, loading, problem, reload } =
    useResource<ComingUpData | null>(load);

  const [minutes, setMinutes] = useState<Partial<Record<PlanRung, string>>>({});
  const [saving, setSaving] = useState(false);
  const [afterMessage, setAfterMessage] = useState('');
  const [afterMinutes, setAfterMinutes] = useState('30');
  const [remindSaved, setRemindSaved] = useState(false);

  if (loading && !appointment) {
    return <Loading />;
  }

  if (!appointment && problem) {
    return <Unreachable problem={problem} onRetry={() => void reload()} />;
  }

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
            accessibilityLabel={`Minutes to ${planRungLabels[rung.rung]}`}
          />
          <Text style={styles.assumed}>
            {rung.assumed ? 'min, assumed' : 'min, yours'}
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
            placeholder="What should future you hear?"
            placeholderTextColor={theme.color.muted}
            accessibilityLabel="What should future you hear"
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
          <Button label="Remind me after" onPress={() => void remindAfter()} />
        </View>
      )}

      <Button label="Back" onPress={() => router.replace('/')} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  after: { gap: theme.space(1), marginTop: theme.space(2) },
  afterLabel: { color: theme.color.muted, fontSize: 13, flex: 1 },
  message: field,
  rung: { flexDirection: 'row', alignItems: 'center', gap: theme.space(1.5) },
  clock: { color: theme.color.text, fontSize: 16, width: 56 },
  passed: { color: theme.color.muted, textDecorationLine: 'line-through' },
  rungLabel: { color: theme.color.muted, fontSize: 15, flex: 1 },
  minutes: {
    minHeight: TOUCH_TARGET,
    width: 72,
    paddingHorizontal: theme.space(1),
    borderRadius: theme.radius,
    borderWidth: 1,
    borderColor: theme.color.border,
    backgroundColor: theme.color.surface,
    color: theme.color.text,
    fontSize: 17,
    textAlign: 'right',
  },
  assumed: { color: theme.color.muted, fontSize: 13, width: 84 },
});
