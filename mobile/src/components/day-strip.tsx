import type { RailData } from '@add/shared';
import {
  clockOf,
  DAY_STRIP_END,
  dayStripDark,
  dayStripFraction,
  dayStripLight,
  doneMinute,
  railSummary,
} from '@add/shared';
import { Text, useColorScheme, View } from 'react-native';
import { makeStyles } from '@/theme';

const useStyles = makeStyles(({ colors, type }) => ({
  strip: { height: 40 },
  drawing: { flex: 1 },
  band: { position: 'absolute', left: 0, right: 0, bottom: 0, height: 12 },
  across: { position: 'absolute', top: 0, bottom: 0 },
  mark: { position: 'absolute', bottom: 0, width: 2, height: 28, backgroundColor: colors.ink },
  rungMark: { height: 20, width: 1 },
  nowLine: { position: 'absolute', top: 0, bottom: 0, width: 2, backgroundColor: colors.now },
  nowClock: { ...type.numeric, position: 'absolute', top: 0, color: colors.now },
  session: { backgroundColor: colors.line },
  done: { backgroundColor: colors.line, opacity: 0.6 },
}));

const from = (minute: number): `${number}%` => `${dayStripFraction(minute) * 100}%`;
const until = (minute: number): `${number}%` => `${100 - dayStripFraction(minute) * 100}%`;

/** Today to scale; a screen reader gets the one sentence and never the drawing. */
export function DayStrip({ rail, nowMinute }: { rail: RailData; nowMinute: number }) {
  const styles = useStyles();
  const stops = useColorScheme() === 'dark' ? dayStripDark : dayStripLight;
  const doneAt = rail.stepSeconds === null ? null : doneMinute(nowMinute, rail.stepSeconds);
  const nearEnd = dayStripFraction(nowMinute) >= 0.75;

  return (
    <View accessible accessibilityLabel={railSummary(rail, nowMinute)} style={styles.strip}>
      <View importantForAccessibility="no-hide-descendants" style={styles.drawing}>
        <View style={styles.band}>
          {stops.map((stop, index) => (
            <View
              key={stop.name}
              style={[
                styles.across,
                {
                  backgroundColor: stop.color,
                  left: from(stop.minute),
                  right: until(stops[index + 1]?.minute ?? DAY_STRIP_END),
                },
              ]}
            />
          ))}
        </View>

        {rail.sessionStartedMinute !== null && (
          <View
            style={[
              styles.across,
              styles.session,
              { left: from(rail.sessionStartedMinute), right: until(nowMinute) },
            ]}
          />
        )}

        {doneAt !== null && (
          <View
            style={[styles.across, styles.done, { left: from(nowMinute), right: until(doneAt) }]}
          />
        )}

        {rail.marks.map((mark) => (
          <View
            key={`${mark.rung}-${mark.minute}`}
            style={[styles.mark, mark.rung !== null && styles.rungMark, { left: from(mark.minute) }]}
          />
        ))}

        <View style={[styles.nowLine, { left: from(nowMinute) }]} />
        <Text
          style={[
            styles.nowClock,
            nearEnd
              ? { right: until(nowMinute), marginRight: 6 }
              : { left: from(nowMinute), marginLeft: 6 },
          ]}
        >
          {clockOf(nowMinute)}
        </Text>
      </View>
    </View>
  );
}
