import { useState } from 'react';
import { TextInput, View } from 'react-native';
import { ApiError, writeProblem } from '@/api/client';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Meta, OneThing, Screen } from '@/components/screen';
import { makeStyles, useTheme } from '@/theme';

const useStyles = makeStyles(({ field, space }) => ({
  fields: { gap: space(1.5) },
  input: field,
}));

export default function SignIn() {
  const { signIn } = useSession();
  const styles = useStyles();
  const { colors } = useTheme();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [code, setCode] = useState('');
  const [needsCode, setNeedsCode] = useState(false);
  const [problem, setProblem] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const submit = async () => {
    setBusy(true);
    setProblem(null);

    try {
      await signIn(email.trim(), password, code.trim() || undefined);
    } catch (error) {
      if (error instanceof ApiError && 'code' in error.errors) {
        setNeedsCode(true);
      }

      setProblem(writeProblem(error));
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen>
      <OneThing>Sign in.</OneThing>
      <Meta>your account is the one you use on the web</Meta>

      <View style={styles.fields}>
        <TextInput
          style={styles.input}
          value={email}
          onChangeText={setEmail}
          placeholder="Email"
          placeholderTextColor={colors.muted}
          autoCapitalize="none"
          autoComplete="email"
          keyboardType="email-address"
          inputMode="email"
          accessibilityLabel="Email"
        />
        <TextInput
          style={styles.input}
          value={password}
          onChangeText={setPassword}
          placeholder="Password"
          placeholderTextColor={colors.muted}
          autoCapitalize="none"
          autoComplete="current-password"
          secureTextEntry
          accessibilityLabel="Password"
        />
        {needsCode && (
          <TextInput
            style={styles.input}
            value={code}
            onChangeText={setCode}
            placeholder="Authentication code"
            placeholderTextColor={colors.muted}
            keyboardType="number-pad"
            inputMode="numeric"
            accessibilityLabel="Authentication code"
          />
        )}
      </View>

      {problem && <Meta>{problem}</Meta>}

      <Button
        label={busy ? 'Signing in' : 'Sign in'}
        tone="primary"
        disabled={busy}
        onPress={() => void submit()}
      />
    </Screen>
  );
}
