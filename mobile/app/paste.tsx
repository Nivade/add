import type { IngestionClassificationData } from '@add/shared';
import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text } from 'react-native';
import { ApiError } from '@/api/client';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Entry } from '@/components/entry';
import { Meta, OneThing, Screen } from '@/components/screen';
import { theme } from '@/theme';

/** Nothing is fetched from anywhere: only what the person pastes here is read. */
export default function Paste() {
  const { token } = useSession();
  const [pasted, setPasted] = useState('');
  const [result, setResult] = useState<IngestionClassificationData | null>(null);
  const [saving, setSaving] = useState(false);

  if (result === null) {
    return (
      <Entry
        question="Paste something that arrived"
        meta="an email, a letter, a message; the app says whether it needs you"
        placeholder="Your car insurance expires October 14."
        label="What arrived"
        multiline
        onSave={async (text) => {
          try {
            setResult(await api.classifyPasted(token as string, text));
            setPasted(text);
          } catch (error) {
            if (error instanceof ApiError && error.status === 503) {
              throw new ApiError(503, {
                text: ['Reading this needs AI, which is off. It can be turned on in settings.'],
              });
            }

            throw error;
          }
        }}
      />
    );
  }

  if (!result.actionable) {
    return (
      <Screen>
        <OneThing>Nothing in this needs you.</OneThing>
        <Button label="Close" onPress={() => router.back()} />
      </Screen>
    );
  }

  const add = async () => {
    setSaving(true);

    try {
      await api.capture(token as string, pasted);
      router.back();
    } finally {
      setSaving(false);
    }
  };

  return (
    <Screen>
      <OneThing>{result.title}</OneThing>
      {result.why && <Text style={styles.line}>{result.why}</Text>}
      <Meta>add it and the app works out the first step</Meta>
      <Button
        label={saving ? 'Adding' : 'Add it'}
        disabled={saving}
        onPress={() => void add()}
      />
      <Button label="Leave it" onPress={() => router.back()} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  line: { color: theme.color.text, fontSize: 16, lineHeight: 24 },
});
