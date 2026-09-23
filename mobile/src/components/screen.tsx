import type { ReactNode } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { theme } from '@/theme';

export function Screen({ children }: { children: ReactNode }) {
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

export function Label({ children }: { children: ReactNode }) {
  return (
    <Text accessibilityRole="header" style={styles.label}>
      {children}
    </Text>
  );
}

/** The one thing. Nothing on a screen is allowed to compete with it. */
export function OneThing({ children }: { children: ReactNode }) {
  return <Text style={styles.oneThing}>{children}</Text>;
}

export function Meta({ children }: { children: ReactNode }) {
  return <Text style={styles.meta}>{children}</Text>;
}

export function Band({ label, children }: { label: string; children: ReactNode }) {
  return (
    <View style={styles.band}>
      <Label>{label}</Label>
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: theme.color.background },
  content: {
    padding: theme.space(3),
    paddingBottom: theme.space(12),
    gap: theme.space(4),
  },
  label: {
    color: theme.color.muted,
    fontSize: 11,
    letterSpacing: 2,
    textTransform: 'uppercase',
    marginBottom: theme.space(1),
  },
  oneThing: {
    color: theme.color.text,
    fontSize: 28,
    lineHeight: 36,
    fontWeight: '600',
  },
  meta: { color: theme.color.muted, fontSize: 15, lineHeight: 22 },
  band: {
    backgroundColor: theme.color.surface,
    borderColor: theme.color.border,
    borderWidth: 1,
    borderRadius: theme.radius,
    padding: theme.space(2),
    gap: theme.space(1),
  },
});
