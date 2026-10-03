import { Pressable, Text } from 'react-native';
import { makeStyles, TOUCH_TARGET } from '@/theme';

const useStyles = makeStyles(({ colors, type }) => ({
  base: { minHeight: TOUCH_TARGET, justifyContent: 'center' },
  pressed: { opacity: 0.6 },
  label: { ...type.body, color: colors.muted, textDecorationLine: 'underline' },
}));

/** Off the one-tap path: still a full touch target, but reads as a line, not a control. */
export function QuietAction({ label, onPress }: { label: string; onPress: () => void }) {
  const styles = useStyles();

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
