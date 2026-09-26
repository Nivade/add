import type { HomeData, NeedsAttentionData, WaitingForResponse } from '@add/shared';
import { formatEstimate, restCountLine, waitingForResponses } from '@add/shared';
import { router } from 'expo-router';
import { useCallback, useState } from 'react';
import { StyleSheet, Text, TextInput, View } from 'react-native';
import { ApiError } from '@/api/client';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Band, Loading, Meta, OneThing, Screen } from '@/components/screen';
import { field, theme } from '@/theme';

function Clarify({
  item,
  onAnswered,
}: {
  item: NeedsAttentionData;
  onAnswered: () => void;
}) {
  const { token } = useSession();
  const [answer, setAnswer] = useState('');
  const [saving, setSaving] = useState(false);
  const [problem, setProblem] = useState<string | null>(null);
  const question = item.clarifyingQuestion ?? '';

  const send = async () => {
    const text = answer.trim();

    if (text === '') {
      return;
    }

    setSaving(true);
    setProblem(null);

    try {
      await api.clarify(token as string, item.id, text);
      onAnswered();
    } catch (error) {
      setProblem(
        error instanceof ApiError
          ? error.firstMessage('That did not go through. Try again.')
          : 'The app could not reach the server.',
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <View style={styles.clarify}>
      <Text style={styles.line}>{item.title}</Text>
      <Meta>{question}</Meta>
      <TextInput
        style={styles.input}
        value={answer}
        onChangeText={setAnswer}
        accessibilityLabel={question}
        returnKeyType="send"
        onSubmitEditing={() => void send()}
      />
      {problem && <Meta>{problem}</Meta>}
      <Button
        label={saving ? 'Saving' : 'Answer'}
        disabled={saving}
        onPress={() => void send()}
      />
    </View>
  );
}

function WaitingFor({
  item,
  onResponded,
}: {
  item: NeedsAttentionData;
  onResponded: () => void;
}) {
  const { token } = useSession();
  const [saving, setSaving] = useState(false);

  const respond = async (response: WaitingForResponse) => {
    setSaving(true);

    try {
      await api.respondToWaitingFor(token as string, item.id, response);
      onResponded();
    } finally {
      setSaving(false);
    }
  };

  return (
    <View style={styles.clarify}>
      <Text style={styles.line}>
        {item.title}
        {item.detail ? ` · ${item.detail}` : ''}
      </Text>
      <View style={styles.responses}>
        {waitingForResponses.map(({ value, label }) => (
          <Button
            key={value}
            label={label}
            disabled={saving}
            onPress={() => void respond(value)}
          />
        ))}
      </View>
    </View>
  );
}

export default function Home() {
  const { token, signOut } = useSession();
  const load = useCallback(() => api.home(token as string), [token]);
  const { data, loading, failed, reload } = useResource<HomeData>(load);

  if (loading && !data) {
    return <Loading />;
  }

  if (failed || !data) {
    return (
      <Screen>
        <OneThing>The app could not reach the server.</OneThing>
        <Button label="Try again" onPress={() => void reload()} />
      </Screen>
    );
  }

  const { rightNow, session, comingUp, reminder, needsAttention, restCount } =
    data;

  const start = async () => {
    if (!rightNow) {
      return;
    }

    await api.startSession(token as string, rightNow.step.id);
    router.push('/focus');
  };

  return (
    <Screen>
      {session ? (
        <>
          <OneThing>
            {session.session.currentStep?.title ?? session.intention.title}
          </OneThing>
          <Meta>part-way through {session.intention.title.toLowerCase()}</Meta>
          <Button
            label="Continue"
            tone="primary"
            onPress={() => router.push('/focus')}
          />
        </>
      ) : rightNow ? (
        <>
          <OneThing>{rightNow.step.title}</OneThing>
          <Meta>
            {formatEstimate(rightNow.step.estimatedSeconds) ?? 'no guess yet'} ·{' '}
            {rightNow.intention.title.toLowerCase()}
          </Meta>
          <Button label="Start" tone="primary" onPress={() => void start()} />
          {rightNow.why.length > 0 && (
            <Band label="Why this one">
              {rightNow.why.map((line) => (
                <Text key={line} style={styles.line}>
                  {line}
                </Text>
              ))}
            </Band>
          )}
        </>
      ) : (
        <>
          <OneThing>Nothing needs you right now.</OneThing>
          <Meta>that is the whole answer</Meta>
        </>
      )}

      {reminder && (
        <Band label="Before you go">
          {reminder.lines.map((line) => (
            <Text key={line} style={styles.line}>
              {line}
            </Text>
          ))}
          <Button
            label="Got it"
            onPress={() => {
              void api
                .dismissReminder(token as string, reminder.id)
                .then(reload);
            }}
          />
        </Band>
      )}

      {comingUp && (
        <Band label="Coming up">
          <Text style={styles.line}>
            {comingUp.title} · {comingUp.inWords}
            {comingUp.kind === 'calendar_event' ? ' · from your calendar' : ''}
          </Text>
          <Button
            label="Open"
            onPress={() =>
              router.push(`/appointment/${comingUp.kind}/${comingUp.id}`)
            }
          />
        </Band>
      )}

      {needsAttention.length > 0 && (
        <Band label="Needs attention">
          {needsAttention.map((item) =>
            item.kind === 'waiting_for' ? (
              <WaitingFor
                key={item.id}
                item={item}
                onResponded={() => void reload()}
              />
            ) : (
              <Clarify
                key={item.id}
                item={item}
                onAnswered={() => void reload()}
              />
            ),
          )}
        </Band>
      )}

      <Meta>{restCountLine(restCount)}</Meta>

      <View style={styles.thumbReach}>
        <Button
          label="Capture a thought"
          tone="primary"
          onPress={() => router.push('/capture')}
        />
        <Button
          label="Waiting for"
          onPress={() => router.push('/waiting-for')}
        />
        <Button
          label="I'm overwhelmed"
          onPress={() => router.push('/overwhelmed')}
        />
        <Button label="Settings" onPress={() => router.push('/settings')} />
        <Button label="Sign out" onPress={() => void signOut()} />
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  line: { color: theme.color.text, fontSize: 16, lineHeight: 24 },
  clarify: { gap: theme.space(1) },
  input: field,
  thumbReach: { gap: theme.space(1.5), marginTop: theme.space(2) },
  responses: { flexDirection: 'row', flexWrap: 'wrap', gap: theme.space(1) },
});
