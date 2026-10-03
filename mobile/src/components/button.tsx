import { Pressable, Text } from 'react-native';
import { makeStyles, TOUCH_TARGET } from '@/theme';

type Props = {
  label: string;
  onPress: () => void;
  tone?: 'primary' | 'outline';
  disabled?: boolean;
};

const useStyles = makeStyles(({ colors, type, radius, space }) => ({
  base: {
    minHeight: TOUCH_TARGET,
    paddingHorizontal: space(2),
    borderRadius: radius.control,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  outline: { backgroundColor: colors.paper, borderColor: colors.field },
  primary: { backgroundColor: colors.now, borderColor: colors.now },
  pressed: { opacity: 0.6 },
  label: { ...type.controlLabel, color: colors.ink },
  primaryLabel: { ...type.actionLabel, color: colors.onNow },
}));

/** One size for every control: skipping weighs what finishing weighs. */
export function Button({ label, onPress, tone = 'outline', disabled }: Props) {
  const styles = useStyles();

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
