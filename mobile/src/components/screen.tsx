import type { ReactNode } from 'react';
import { ScrollView, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { makeStyles } from '@/theme';

const useStyles = makeStyles(({ colors, type, space }) => ({
  safe: { flex: 1, backgroundColor: colors.paper },
  content: {
    paddingHorizontal: space(2.5),
    paddingTop: space(3),
    paddingBottom: space(12),
    gap: space(5),
  },
  label: { ...type.bandHeading, color: colors.muted },
  oneThing: { ...type.oneThing, color: colors.ink },
  meta: { ...type.body, color: colors.muted },
  band: { gap: space(1) },
}));

export function Screen({ children }: { children: ReactNode }) {
  const styles = useStyles();

  return (
    <SafeAreaView style={styles.safe}>
      <ScrollView
        contentContainerStyle={styles.content}
        keyboardShouldPersistTaps="handled"
      >
        {children}
      </ScrollView>
    </SafeAreaView>
  );
}

/** What every screen shows while it is asking the server, so none of them phrases the wait differently. */
export function Loading() {
  return (
    <Screen>
      <Meta>one moment</Meta>
    </Screen>
  );
}

function Label({ children }: { children: ReactNode }) {
  const styles = useStyles();

  return (
    <Text accessibilityRole="header" style={styles.label}>
      {children}
    </Text>
  );
}

/** The one thing. Nothing on a screen is allowed to compete with it. */
export function OneThing({ children }: { children: ReactNode }) {
  const styles = useStyles();

  return <Text style={styles.oneThing}>{children}</Text>;
}

export function Meta({ children }: { children: ReactNode }) {
  const styles = useStyles();

  return <Text style={styles.meta}>{children}</Text>;
}

/** A band is a question and its answer, set apart by space. No boxes: a box implies a list to work through. */
export function Band({ label, children }: { label: string; children: ReactNode }) {
  const styles = useStyles();

  return (
    <View style={styles.band}>
      <Label>{label}</Label>
      {children}
    </View>
  );
}
