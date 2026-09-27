import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, TextInput } from 'react-native';
import { writeProblem } from '@/api/client';
import { Button } from '@/components/button';
import { Meta, OneThing, Screen } from '@/components/screen';
import { field, theme } from '@/theme';

/** One question, one field, and leaving empty-handed is always allowed. */
export function Entry({
  question,
  meta,
  placeholder,
  label,
  multiline = false,
  second,
  onSave,
}: {
  question: string;
  meta: string;
  placeholder: string;
  label: string;
  multiline?: boolean;
  second?: { placeholder: string; label: string };
  onSave: (text: string, secondText: string) => Promise<void>;
}) {
  const [text, setText] = useState('');
  const [secondText, setSecondText] = useState('');
  const [saving, setSaving] = useState(false);
  const [problem, setProblem] = useState<string | null>(null);

  const save = async () => {
    const trimmed = text.trim();

    if (trimmed === '') {
      router.back();

      return;
    }

    setSaving(true);
    setProblem(null);

    try {
      await onSave(trimmed, secondText.trim());
    } catch (error) {
      setProblem(writeProblem(error));
    } finally {
      setSaving(false);
    }
  };

  return (
    <Screen>
      <OneThing>{question}</OneThing>
      <Meta>{meta}</Meta>

      <TextInput
        style={[styles.input, multiline && styles.multiline]}
        value={text}
        onChangeText={setText}
        placeholder={placeholder}
        placeholderTextColor={theme.color.muted}
        multiline={multiline}
        autoFocus
        accessibilityLabel={label}
      />
      {second && (
        <TextInput
          style={styles.input}
          value={secondText}
          onChangeText={setSecondText}
          placeholder={second.placeholder}
          placeholderTextColor={theme.color.muted}
          accessibilityLabel={second.label}
        />
      )}
      {problem && <Meta>{problem}</Meta>}

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
  input: field,
  multiline: {
    minHeight: 160,
    paddingVertical: theme.space(1.5),
    textAlignVertical: 'top',
  },
});
