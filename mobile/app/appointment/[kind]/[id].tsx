import type { AppointmentKind, HomeData, PlanRung } from '@add/shared';
import { planRungLabels } from '@add/shared';
import { router, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { StyleSheet, Text, TextInput, View } from 'react-native';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Loading, Meta, OneThing, Screen } from '@/components/screen';
import { TOUCH_TARGET, theme } from '@/theme';

/** Where a reminder lands. Every number here is the person's to overrule. */
export default function Appointment() {
  const { kind, id } = useLocalSearchParams<{ kind: string; id: string }>();
  const { token } = useSession();
  const load = useCallback(() => api.home(token as string), [token]);
  const { data, loading } = useResource<HomeData>(load);
  const [minutes, setMinutes] = useState<Partial<Record<PlanRung, string>>>({});
  const [saving, setSaving] = useState(false);

  if (loading && !data) {
    return <Loading />;
  }

  const appointment =
    data?.comingUp && data.comingUp.id === id && data.comingUp.kind === kind
      ? data.comingUp
      : null;

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
        appointment.kind as AppointmentKind,
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

  return (
    <Screen>
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

      <Button label="Back" onPress={() => router.replace('/')} />
    </Screen>
  );
}

const styles = StyleSheet.create({
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
