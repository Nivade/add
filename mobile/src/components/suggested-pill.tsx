import { suggestedLabel } from '@add/shared';
import { StyleSheet, Text } from 'react-native';
import { theme } from '@/theme';

/** A step the app wrote says who wrote it, so it is never mistaken for one they did. */
export function SuggestedPill() {
  return <Text style={styles.pill}>{suggestedLabel}</Text>;
}

/** The device clock is the person's clock: every request already tells the server its zone. */
export function nowMinute(): number {
  const now = new Date();

  return now.getHours() * 60 + now.getMinutes();
}

const styles = StyleSheet.create({
  pill: {
    alignSelf: 'flex-start',
    borderColor: theme.color.border,
    borderWidth: 1,
    borderRadius: 999,
    color: theme.color.muted,
    fontSize: 14,
    paddingHorizontal: theme.space(1.5),
    paddingVertical: theme.space(0.5),
  },
});
