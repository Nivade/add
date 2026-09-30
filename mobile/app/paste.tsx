import type { IngestionClassificationData } from '@add/shared';
import { entryCopy } from '@add/shared';
import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text } from 'react-native';
import { ApiError } from '@/api/client';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Entry } from '@/components/entry';
import { Meta, OneThing, Screen } from '@/components/screen';
import { line } from '@/theme';

/** Nothing is fetched from anywhere: only what the person pastes here is read. */
export default function Paste() {
  const { token } = useSession();
  const [pasted, setPasted] = useState('');
  const [result, setResult] = useState<IngestionClassificationData | null>(null);
  const [saving, setSaving] = useState(false);

  if (result === null) {
    return (
      <Entry
        question={entryCopy.paste.question}
        meta={entryCopy.paste.meta}
        placeholder={entryCopy.paste.placeholder}
        label={entryCopy.paste.label}
        multiline
        onSave={async (text) => {
          try {
            setResult(await api.classifyPasted(token as string, text));
            setPasted(text);
          } catch (error) {
            if (error instanceof ApiError && error.status === 503 && error.serverMessage) {
              throw new ApiError(503, { text: [error.serverMessage] });
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
        <OneThing>{entryCopy.paste.nothingNeeded}</OneThing>
        <Button label={entryCopy.paste.close} onPress={() => router.back()} />
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
        label={saving ? 'Adding' : entryCopy.paste.add}
        disabled={saving}
        onPress={() => void add()}
      />
      <Button label={entryCopy.paste.leave} onPress={() => router.back()} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  line,
});
