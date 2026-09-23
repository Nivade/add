import { Pressable, StyleSheet, Text } from 'react-native';
import { TOUCH_TARGET, theme } from '@/theme';

type Props = {
  label: string;
  onPress: () => void;
  tone?: 'primary' | 'outline';
  disabled?: boolean;
};

/** One size for every control: skipping weighs what finishing weighs. */
export function Button({ label, onPress, tone = 'outline', disabled }: Props) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      accessibilityState={{ disabled: Boolean(disabled) }}
      disabled={disabled}
      onPress={onPress}
      style={({ pressed }) => [
        styles.base,
        tone === 'primary' ? styles.primary : styles.outline,
        (pressed || disabled) && styles.pressed,
      ]}
    >
      <Text style={tone === 'primary' ? styles.primaryLabel : styles.label}>
        {label}
      </Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: {
    minHeight: TOUCH_TARGET,
    paddingHorizontal: theme.space(2),
    borderRadius: theme.radius,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  outline: {
    backgroundColor: theme.color.surface,
    borderColor: theme.color.border,
  },
  primary: { backgroundColor: theme.color.now, borderColor: theme.color.now },
  pressed: { opacity: 0.6 },
  label: { color: theme.color.text, fontSize: 16, fontWeight: '600' },
  primaryLabel: {
    color: theme.color.background,
    fontSize: 16,
    fontWeight: '700',
  },
});
