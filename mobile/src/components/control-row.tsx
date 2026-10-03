import * as Haptics from 'expo-haptics';
import type { ReactNode } from 'react';
import { Pressable, Text, View } from 'react-native';
import { makeStyles, TOUCH_TARGET } from '@/theme';

const useStyles = makeStyles(({ colors, type, radius, space }) => ({
  row: { gap: space(1) },
  name: { ...type.small, color: colors.muted },
  controls: { flexDirection: 'row', gap: space(1) },
  control: {
    flex: 1,
    minHeight: TOUCH_TARGET,
    paddingHorizontal: space(0.5),
    paddingVertical: space(1),
    borderRadius: radius.control,
    borderWidth: 1,
    borderColor: colors.field,
    backgroundColor: colors.paper,
    alignItems: 'center',
    justifyContent: 'center',
    gap: space(0.25),
  },
  pressed: { opacity: 0.6 },
  label: { ...type.controlLabel, color: colors.ink, textAlign: 'center' },
  hint: { ...type.small, color: colors.muted, textAlign: 'center' },
}));

/** Three equal controls under a plain name for what they act on. */
export function ControlRow({ label, children }: { label: string; children: ReactNode }) {
  const styles = useStyles();

  return (
    <View accessibilityRole="toolbar" accessibilityLabel={label} style={styles.row}>
      <Text style={styles.name}>{label}</Text>
      <View style={styles.controls}>{children}</View>
    </View>
  );
}

/** One size, one weight, one haptic: skipping weighs what finishing weighs. */
export function Control({
  label,
  hint,
  onPress,
  disabled,
}: {
  label: string;
  hint: string;
  onPress: () => void;
  disabled?: boolean;
}) {
  const styles = useStyles();

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      accessibilityHint={hint}
      accessibilityState={{ disabled: Boolean(disabled) }}
      disabled={disabled}
      onPress={() => {
        void Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
        onPress();
      }}
      style={({ pressed }) => [styles.control, (pressed || disabled) && styles.pressed]}
    >
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.hint}>{hint}</Text>
    </Pressable>
  );
}
