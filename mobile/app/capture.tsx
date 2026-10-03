import type { CaptureSource } from '@add/shared';
import { captureCopy } from '@add/shared';
import {
  ExpoSpeechRecognitionModule,
  useSpeechRecognitionEvent,
} from 'expo-speech-recognition';
import { router } from 'expo-router';
import { useRef, useState } from 'react';
import { TextInput } from 'react-native';
import { deviceLocale, writeProblem } from '@/api/client';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';
import { keepCapture, wasNeverAnswered } from '@/capture/outbox';
import { Button } from '@/components/button';
import { Meta, OneThing, Screen } from '@/components/screen';
import { makeStyles, useTheme } from '@/theme';

const useStyles = makeStyles(({ type, field, space }) => ({
  input: {
    ...field,
    ...type.lead,
    minHeight: 160,
    padding: space(2),
    textAlignVertical: 'top',
  },
}));

export default function Capture() {
  const { token } = useSession();
  const styles = useStyles();
  const { colors } = useTheme();
  const [body, setBody] = useState('');
  const [listening, setListening] = useState(false);
  const [saving, setSaving] = useState(false);
  const [problem, setProblem] = useState<string | null>(null);
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
    ExpoSpeechRecognitionModule.start({ lang: deviceLocale(), interimResults: true });
  };

  const save = async () => {
    const text = body.trim();

    if (text === '') {
      router.back();

      return;
    }

    setSaving(true);
    setProblem(null);

    try {
      await api.capture(token as string, text, source.current);
      router.back();
    } catch (error) {
      if (!wasNeverAnswered(error)) {
        setProblem(writeProblem(error));

        return;
      }

      await keepCapture(text, source.current);
      router.dismissTo({
        pathname: '/',
        params: { notice: captureCopy.keptOffline },
      });
    } finally {
      setSaving(false);
    }
  };

  return (
    <Screen>
      <OneThing>{captureCopy.question}</OneThing>
      <Meta>no title, no category, no due date</Meta>

      <TextInput
        style={styles.input}
        value={body}
        onChangeText={(text) => {
          source.current = 'text';
          setBody(text);
        }}
        placeholder="Type it, or hold the mic."
        placeholderTextColor={colors.muted}
        multiline
        autoFocus
        accessibilityLabel="Your thought"
      />

      <Button
        label={listening ? captureCopy.listening : 'Say it instead'}
        onPress={() =>
          listening ? ExpoSpeechRecognitionModule.stop() : void listen()
        }
      />

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
