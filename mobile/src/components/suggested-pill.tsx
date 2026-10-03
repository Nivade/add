import { suggestedLabel } from '@add/shared';
import { Text } from 'react-native';
import { makeStyles } from '@/theme';

const useStyles = makeStyles(({ colors, type, space }) => ({
  pill: {
    ...type.small,
    alignSelf: 'flex-start',
    borderColor: colors.field,
    borderWidth: 1,
    borderRadius: 999,
    color: colors.muted,
    paddingHorizontal: space(1.5),
    paddingVertical: space(0.5),
  },
}));

/** A step the app wrote says who wrote it, so it is never mistaken for one they did. */
export function SuggestedPill() {
  const styles = useStyles();

  return <Text style={styles.pill}>{suggestedLabel}</Text>;
}

/** The device clock is the person's clock: every request already tells the server its zone. */
export function nowMinute(): number {
  const now = new Date();

  return now.getHours() * 60 + now.getMinutes();
}
