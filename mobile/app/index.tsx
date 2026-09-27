import type {
  HomeData,
  JustFinishedData,
  NeedsAttentionData,
} from '@add/shared';
import {
  commitmentCopy,
  formatEstimate,
  recurrenceLine,
  restCountLine,
  waitingForResponses,
} from '@add/shared';
import { router } from 'expo-router';
import { useCallback, useState } from 'react';
import { StyleSheet, Text, TextInput, View } from 'react-native';
import { ApiError } from '@/api/client';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { CommitmentRow } from '@/components/commitment-row';
import { QuietAction } from '@/components/quiet-action';
import { Responses } from '@/components/responses';
import { Band, Loading, Meta, OneThing, Screen } from '@/components/screen';
import { field, line, theme } from '@/theme';

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

  return (
    <View style={styles.clarify}>
      <Text style={styles.line}>
        {item.title}
        {item.detail ? ` · ${item.detail}` : ''}
      </Text>
      <Responses
        responses={waitingForResponses}
        onRespond={async (response) => {
          await api.respondToWaitingFor(token as string, item.id, response);
          onResponded();
        }}
      />
    </View>
  );
}

function JustFinished({
  finished,
  onRepeated,
}: {
  finished: JustFinishedData;
  onRepeated: () => void;
}) {
  const { token } = useSession();
  const [everyDays, setEveryDays] = useState('7');
  const [saving, setSaving] = useState(false);

  const repeat = async () => {
    const days = Number(everyDays);

    if (!Number.isInteger(days) || days < 1) {
      return;
    }

    setSaving(true);

    try {
      await api.repeatIntention(token as string, finished.id, days);
      onRepeated();
    } finally {
      setSaving(false);
    }
  };

  return (
    <Band label="Just finished">
      <Text style={styles.line}>{finished.title}</Text>
      {finished.recurrenceEveryDays ? (
        <Meta>{recurrenceLine(finished.recurrenceEveryDays)}</Meta>
      ) : (
        <>
          <View style={styles.repeat}>
            <Meta>Repeat every</Meta>
            <TextInput
              style={[styles.input, styles.days]}
              value={everyDays}
              onChangeText={setEveryDays}
              keyboardType="number-pad"
              inputMode="numeric"
              accessibilityLabel="Repeat every how many days"
            />
            <Meta>days</Meta>
          </View>
          <Button
            label={saving ? 'Saving' : 'Repeat'}
            disabled={saving}
            onPress={() => void repeat()}
          />
        </>
      )}
    </Band>
  );
}

export default function Home() {
  const { token, signOut } = useSession();
  const load = useCallback(() => api.home(token as string), [token]);
  const { data, loading, problem, reload } = useResource<HomeData>(load);

  if (loading && !data) {
    return <Loading />;
  }

  if (!data) {
    return (
      <Screen>
        <OneThing>{problem ?? 'The app could not reach the server.'}</OneThing>
        <Button label="Try again" onPress={() => void reload()} />
      </Screen>
    );
  }

  const {
    rightNow,
    rightNowIsCommitment,
    hasOpenCommitments,
    session,
    comingUp,
    reminder,
    justFinished,
    needsAttention,
    restCount,
  } = data;

  const promote = async () => {
    if (rightNow) {
      await api.promoteToCommitment(token as string, rightNow.intention.id);
      await reload();
    }
  };

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
              {rightNowIsCommitment ? (
                <Text style={styles.line}>{commitmentCopy.promised}</Text>
              ) : (
                <QuietAction
                  label={commitmentCopy.promise}
                  onPress={() => void promote()}
                />
              )}
            </Band>
          )}
        </>
      ) : (
        <>
          <OneThing>Nothing needs you right now.</OneThing>
          <Meta>that is the whole answer</Meta>
        </>
      )}

      {justFinished && (
        <JustFinished finished={justFinished} onRepeated={() => void reload()} />
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
          {needsAttention.map((item) => {
            switch (item.kind) {
              case 'waiting_for':
                return (
                  <WaitingFor
                    key={item.id}
                    item={item}
                    onResponded={() => void reload()}
                  />
                );
              case 'commitment':
                return (
                  <CommitmentRow
                    key={item.id}
                    id={item.id}
                    description={item.title}
                    provenance={item.provenance}
                    awaitingConfirmation={item.awaitingConfirmation}
                    onResponded={() => void reload()}
                  />
                );
              case 'intention':
                return (
                  <Clarify
                    key={item.id}
                    item={item}
                    onAnswered={() => void reload()}
                  />
                );
            }
          })}
        </Band>
      )}

      <Meta>{restCountLine(restCount)}</Meta>
      {hasOpenCommitments && (
        <QuietAction
          label={commitmentCopy.list}
          onPress={() => router.push('/commitments')}
        />
      )}

      <View style={styles.thumbReach}>
        <Button
          label="Capture a thought"
          tone="primary"
          onPress={() => router.push('/capture')}
        />
        <Button
          label="Something else"
          onPress={() => router.push('/add')}
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
  line,
  clarify: { gap: theme.space(1) },
  input: field,
  thumbReach: { gap: theme.space(1.5), marginTop: theme.space(2) },
  repeat: { flexDirection: 'row', alignItems: 'center', gap: theme.space(1) },
  days: { width: 72, textAlign: 'right' },
});
