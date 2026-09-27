import { Pressable, StyleSheet, Text } from 'react-native';
import { TOUCH_TARGET, theme } from '@/theme';

/** Off the one-tap path: still a full touch target, but reads as a line, not a control. */
export function QuietAction({ label, onPress }: { label: string; onPress: () => void }) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      onPress={onPress}
      style={({ pressed }) => [styles.base, pressed && styles.pressed]}
    >
      <Text style={styles.label}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: { minHeight: TOUCH_TARGET, justifyContent: 'center' },
  pressed: { opacity: 0.6 },
  label: {
    color: theme.color.muted,
    fontSize: 15,
    textDecorationLine: 'underline',
  },
});
