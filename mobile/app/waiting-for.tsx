import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, TextInput } from 'react-native';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Meta, OneThing, Screen } from '@/components/screen';
import { theme } from '@/theme';

export default function WaitingFor() {
  const { token } = useSession();
  const [subject, setSubject] = useState('');
  const [note, setNote] = useState('');
  const [saving, setSaving] = useState(false);

  const save = async () => {
    const who = subject.trim();

    if (who === '') {
      router.back();

      return;
    }

    setSaving(true);

    try {
      await api.createWaitingFor(token as string, who, note.trim());
      router.back();
    } finally {
      setSaving(false);
    }
  };

  return (
    <Screen>
      <OneThing>Who or what are you waiting on?</OneThing>
      <Meta>nothing to do until they get back to you</Meta>

      <TextInput
        style={styles.input}
        value={subject}
        onChangeText={setSubject}
        placeholder="John"
        placeholderTextColor={theme.color.muted}
        autoFocus
        accessibilityLabel="Who or what"
      />

      <TextInput
        style={styles.input}
        value={note}
        onChangeText={setNote}
        placeholder="the contract"
        placeholderTextColor={theme.color.muted}
        accessibilityLabel="What for"
      />

      <Button
        label={saving ? 'Saving' : 'Save it'}
        tone="primary"
        disabled={saving}
        onPress={() => void save()}
      />

      <Button label="Not now" onPress={() => router.back()} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  input: {
    minHeight: 48,
    padding: theme.space(2),
    borderRadius: theme.radius,
    borderWidth: 1,
    borderColor: theme.color.border,
    backgroundColor: theme.color.surface,
    color: theme.color.text,
    fontSize: 18,
  },
});
