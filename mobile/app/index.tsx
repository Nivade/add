import type { HomeData } from '@add/shared';
import { formatEstimate, restCountLine } from '@add/shared';
import { router } from 'expo-router';
import { useCallback } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Band, Meta, OneThing, Screen } from '@/components/screen';
import { theme } from '@/theme';

export default function Home() {
  const { token, signOut } = useSession();
  const load = useCallback(() => api.home(token as string), [token]);
  const { data, loading, failed, reload } = useResource<HomeData>(load);

  if (loading && !data) {
    return (
      <Screen>
        <Meta>one moment</Meta>
      </Screen>
    );
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
          {needsAttention.map((intention) => (
            <Text key={intention.id} style={styles.line}>
              {intention.title}
            </Text>
          ))}
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
          label="I'm overwhelmed"
          onPress={() => router.push('/overwhelmed')}
        />
        <Button label="Sign out" onPress={() => void signOut()} />
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  line: { color: theme.color.text, fontSize: 16, lineHeight: 24 },
  thumbReach: { gap: theme.space(1.5), marginTop: theme.space(2) },
});
