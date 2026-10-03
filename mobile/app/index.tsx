import type {
  HomeData,
  NeedsAttentionData,
} from '@add/shared';
import {
  checkInCopy,
  checkInQuestions,
  checkInResponses,
  comingUpCopy,
  commitmentCopy,
  estimateLine,
  focusCopy,
  homeBands,
  homeCopy,
  nothingNeedsYou,
  notHereLabels,
  partOfLine,
  restCountLine,
  returnCopy,
  sortingLine,
  unsortedLine,
  waitingForResponses,
} from '@add/shared';
import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { Text, TextInput, View } from 'react-native';
import { writeProblem } from '@/api/client';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { useSendKeptCaptures } from '@/capture/outbox';
import { Button } from '@/components/button';
import { CommitmentRow } from '@/components/commitment-row';
import { DayStrip } from '@/components/day-strip';
import { QuietAction } from '@/components/quiet-action';
import { Responses } from '@/components/responses';
import { Band, Meta, OneThing, Screen } from '@/components/screen';
import { Pending, StaleNote } from '@/components/resource-state';
import { SortedBand } from '@/components/sorted-band';
import { SuggestedPill } from '@/components/suggested-pill';
import { railOf } from '@/rail';
import { makeStyles } from '@/theme';

const useStyles = makeStyles(({ colors, type, field, space }) => ({
  line: { ...type.body, color: colors.ink },
  clarify: { gap: space(1) },
  input: field,
  top: { paddingHorizontal: space(2.5), paddingTop: space(1), gap: space(0.5) },
  settings: { alignItems: 'flex-end' },
  capture: { flex: 1 },
}));

function Clarify({
  item,
  onAnswered,
}: {
  item: NeedsAttentionData;
  onAnswered: () => void;
}) {
  const { token } = useSession();
  const styles = useStyles();
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
      setProblem(writeProblem(error));
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
  const styles = useStyles();

  return (
    <View style={styles.clarify}>
      <Text style={styles.line}>
        {item.title}
        {item.detail ? `: ${item.detail}` : ''}
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

export default function Home() {
  const { token } = useSession();
  const styles = useStyles();
  const { notice } = useLocalSearchParams<{ notice?: string }>();
  useSendKeptCaptures();
  const load = useCallback(() => api.home(token as string), [token]);
  const resource = useResource<HomeData>(load);
  const sortingCount =
    resource.status === 'ready' ? resource.data.sortingCount : 0;
  const { reload: reloadHome } = resource;

  /** One read at a time, and only while home is on screen. */
  useFocusEffect(
    useCallback(() => {
      if (sortingCount === 0) {
        return;
      }

      let stopped = false;
      let timer: ReturnType<typeof setTimeout>;
      const next = () => {
        timer = setTimeout(async () => {
          await reloadHome();

          if (!stopped) {
            next();
          }
        }, 3000);
      };

      next();

      return () => {
        stopped = true;
        clearTimeout(timer);
      };
    }, [sortingCount, reloadHome]),
  );

  if (resource.status !== 'ready') {
    return <Pending resource={resource} />;
  }

  const { data, problem, reload } = resource;

  const {
    rightNow,
    rightNowIsCommitment,
    hasOpenCommitments,
    session,
    comingUp,
    reminder,
    needsAttention,
    restCount,
    checkIn,
    sorted,
    sortedMore,
    unsortedCount,
    aiConsented,
  } = data;
  const unsorted = unsortedLine(unsortedCount, aiConsented);
  const leave = comingUp?.plan?.rungs.find(({ rung }) => rung === 'leave');

  const promote = async () => {
    if (rightNow) {
      await api.promoteToCommitment(token as string, rightNow.intention.id);
      await reload();
    }
  };

  const notHere = async () => {
    if (rightNow?.assumedPlace) {
      await api.notHere(token as string, rightNow.assumedPlace);
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

  const rail = railOf(data, new Date());

  return (
    <Screen
      top={
        <View style={styles.top}>
          <DayStrip rail={rail} />
          <View style={styles.settings}>
            <QuietAction label="Settings" onPress={() => router.push('/settings')} />
          </View>
        </View>
      }
      bottom={
        <>
          <View style={styles.capture}>
            <Button
              label="Capture a thought"
              tone="primary"
              onPress={() => router.push('/capture')}
            />
          </View>
          <Button
            label="I'm overwhelmed"
            onPress={() => router.push('/overwhelmed')}
          />
        </>
      }
    >
      <StaleNote problem={problem} />
      {notice && (
        <Text accessibilityLiveRegion="polite" style={styles.line}>
          {notice}
        </Text>
      )}
      {session ? (
        <>
          <OneThing>
            {session.returning
              ? returnCopy.welcome
              : (session.session.currentStep?.title ?? session.intention.title)}
          </OneThing>
          <Meta>
            {session.returning
              ? returnCopy.workingOn(session.intention.title)
              : returnCopy.partWay(session.intention.title)}
          </Meta>
          <Button
            label={focusCopy.continue}
            tone="primary"
            onPress={() => router.push('/focus')}
          />
        </>
      ) : rightNow ? (
        <>
          <OneThing>{rightNow.step.title}</OneThing>
          <Meta>
            {`${estimateLine(rightNow.step.estimatedSeconds, rail.nowMinute)} ${partOfLine(rightNow.intention.title)}`}
          </Meta>
          <Button label={focusCopy.start} tone="primary" onPress={() => void start()} />
          {rightNow.step.generated && <SuggestedPill />}
        </>
      ) : (
        <>
          <OneThing>{nothingNeedsYou}</OneThing>
          <Meta>{homeCopy.wholeAnswer}</Meta>
        </>
      )}

      <Text accessibilityLiveRegion="polite" style={styles.line}>
        {sortingCount > 0 ? sortingLine(sortingCount) : ''}
      </Text>

      {unsortedCount > 0 && (
        <View>
          <Text accessibilityLiveRegion="polite" style={styles.line}>
            {unsorted.line}
          </Text>
          {unsorted.action && (
            <QuietAction label={unsorted.action} onPress={() => router.push('/settings')} />
          )}
        </View>
      )}

      {!session && rightNow && rightNow.why.length > 0 && (
        <Band label={homeBands.why}>
          {rightNow.why.map((line) => (
            <Text key={line} style={styles.line}>
              {line}
            </Text>
          ))}
          {rightNow.assumedPlace && (
            <QuietAction
              label={notHereLabels[rightNow.assumedPlace]}
              onPress={() => void notHere()}
            />
          )}
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

      <SortedBand sorted={sorted} more={sortedMore} onChanged={() => void reload()} />

      {reminder && (
        <Band label={homeBands.beforeYouGo}>
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
        <Band label={homeBands.comingUp}>
          <Text style={styles.line}>
            {comingUpCopy.line(comingUp.title, comingUp.inWords)}
            {comingUp.kind === 'calendar_event' ? ` ${comingUpCopy.fromCalendar}` : ''}
          </Text>
          {leave && <Meta>{comingUpCopy.leaveAt(leave.clock)}</Meta>}
          {(comingUp.plan || comingUp.kind === 'calendar_event') && (
            <Button
              label={comingUpCopy.planFor}
              onPress={() =>
                router.push(`/appointment/${comingUp.kind}/${comingUp.id}`)
              }
            />
          )}
        </Band>
      )}

      {needsAttention.length > 0 && (
        <Band label={homeBands.needsAttention}>
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

      {checkIn && (
        <Band label={checkInCopy.band}>
          <Text style={styles.line}>{checkInQuestions[checkIn]}</Text>
          <Meta>{checkInCopy.meta}</Meta>
          <Responses
            responses={checkInResponses}
            onRespond={async (answer) => {
              await api.checkIn(token as string, checkIn, answer);
              await reload();
            }}
          />
        </Band>
      )}

      <Meta>{restCountLine(restCount)}</Meta>
      {hasOpenCommitments && (
        <QuietAction
          label={commitmentCopy.list}
          onPress={() => router.push('/commitments')}
        />
      )}
    </Screen>
  );
}
