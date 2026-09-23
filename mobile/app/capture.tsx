import type { CaptureSource } from '@add/shared';
import {
  ExpoSpeechRecognitionModule,
  useSpeechRecognitionEvent,
} from 'expo-speech-recognition';
import { router } from 'expo-router';
import { useRef, useState } from 'react';
import { StyleSheet, TextInput } from 'react-native';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Meta, OneThing, Screen } from '@/components/screen';
import { theme } from '@/theme';

export default function Capture() {
  const { token } = useSession();
  const [body, setBody] = useState('');
  const [listening, setListening] = useState(false);
  const [saving, setSaving] = useState(false);
  const source = useRef<CaptureSource>('text');

  useSpeechRecognitionEvent('result', (event) => {
    const said = event.results[0]?.transcript ?? '';

    if (said) {
      source.current = 'voice';
      setBody(said);
    }
  });

  useSpeechRecognitionEvent('end', () => setListening(false));
  useSpeechRecognitionEvent('error', () => setListening(false));

  const listen = async () => {
    const permission =
      await ExpoSpeechRecognitionModule.requestPermissionsAsync();

    if (!permission.granted) {
      return;
    }

    setListening(true);
    ExpoSpeechRecognitionModule.start({ lang: 'en-US', interimResults: true });
  };

  const save = async () => {
    const text = body.trim();

    if (text === '') {
      router.back();

      return;
    }

    setSaving(true);

    try {
      await api.capture(token as string, text, source.current);
      router.back();
    } finally {
      setSaving(false);
    }
  };

  return (
    <Screen>
      <OneThing>What's on your mind?</OneThing>
      <Meta>no title, no category, no due date</Meta>

      <TextInput
        style={styles.input}
        value={body}
        onChangeText={(text) => {
          source.current = 'text';
          setBody(text);
        }}
        placeholder="Type it, or hold the mic."
        placeholderTextColor={theme.color.muted}
        multiline
        autoFocus
        accessibilityLabel="Your thought"
      />

      <Button
        label={listening ? 'Listening — tap to stop' : 'Say it instead'}
        onPress={() =>
          listening ? ExpoSpeechRecognitionModule.stop() : void listen()
        }
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
    minHeight: 160,
    padding: theme.space(2),
    borderRadius: theme.radius,
    borderWidth: 1,
    borderColor: theme.color.border,
    backgroundColor: theme.color.surface,
    color: theme.color.text,
    fontSize: 20,
    lineHeight: 28,
    textAlignVertical: 'top',
  },
});
