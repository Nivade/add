import type { CommitmentListData } from '@add/shared';
import { commitmentCopy } from '@add/shared';
import { router } from 'expo-router';
import { useCallback } from 'react';
import { StyleSheet, View } from 'react-native';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { CommitmentRow } from '@/components/commitment-row';
import { Loading, Meta, OneThing, Screen } from '@/components/screen';
import { StaleNote, Unreachable } from '@/components/unreachable';
import { theme } from '@/theme';

/** Reached only from home; home stays the place things are chosen from. */
export default function Commitments() {
  const { token } = useSession();
  const load = useCallback(() => api.commitments(token as string), [token]);
  const { data, loading, problem, reload } = useResource<CommitmentListData>(load);

  if (loading && !data) {
    return <Loading />;
  }

  if (!data) {
    return <Unreachable problem={problem} onRetry={() => void reload()} />;
  }

  const open = data.commitments;

  return (
    <Screen>
      <StaleNote problem={problem} />
      <OneThing>{commitmentCopy.listTitle}</OneThing>
      {open.length === 0 && (
        <Meta>{commitmentCopy.listEmpty}</Meta>
      )}
      {open.map((commitment) => (
        <View key={commitment.id} style={styles.divided}>
          <CommitmentRow
            id={commitment.id}
            description={commitment.description}
            provenance={commitment.provenance}
            awaitingConfirmation={commitment.awaitingConfirmation}
            onResponded={() => void reload()}
          />
        </View>
      ))}
      <Button label="Back" onPress={() => router.back()} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  divided: {
    paddingTop: theme.space(2),
    borderTopWidth: 1,
    borderTopColor: theme.color.border,
  },
});
