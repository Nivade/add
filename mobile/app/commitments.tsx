import type { CommitmentData, CommitmentListData, CommitmentResponse } from '@add/shared';
import { commitmentProvenanceLabels, commitmentResponses } from '@add/shared';
import { router } from 'expo-router';
import { useCallback, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Loading, Meta, OneThing, Screen } from '@/components/screen';
import { theme } from '@/theme';

function Row({
  commitment,
  onResponded,
}: {
  commitment: CommitmentData;
  onResponded: () => void;
}) {
  const { token } = useSession();
  const [saving, setSaving] = useState(false);
  const inferred =
    commitment.provenance === 'system_inferred' && commitment.confirmedAt === null;

  const respond = async (response: CommitmentResponse) => {
    setSaving(true);

    try {
      await api.respondToCommitment(token as string, commitment.id, response);
      onResponded();
    } finally {
      setSaving(false);
    }
  };

  return (
    <View style={styles.row}>
      <Text style={styles.line}>{commitment.description}</Text>
      <Meta>{commitmentProvenanceLabels[commitment.provenance]}</Meta>
      <View style={styles.responses}>
        {commitmentResponses(inferred).map(({ value, label }) => (
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

/** Reached only from home's one commitment; home stays the place things are chosen from. */
export default function Commitments() {
  const { token } = useSession();
  const load = useCallback(() => api.commitments(token as string), [token]);
  const { data, loading, reload } = useResource<CommitmentListData>(load);

  if (loading && !data) {
    return <Loading />;
  }

  const open = data?.commitments ?? [];

  return (
    <Screen>
      <OneThing>What you said you'd do</OneThing>
      {open.length === 0 && (
        <Meta>nothing is open; anything you say you'll do lands here</Meta>
      )}
      {open.map((commitment) => (
        <Row
          key={commitment.id}
          commitment={commitment}
          onResponded={() => void reload()}
        />
      ))}
      <Button label="Back" onPress={() => router.back()} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  row: {
    gap: theme.space(1),
    paddingTop: theme.space(2),
    borderTopWidth: 1,
    borderTopColor: theme.color.border,
  },
  line: { color: theme.color.text, fontSize: 16, lineHeight: 24 },
  responses: { flexDirection: 'row', flexWrap: 'wrap', gap: theme.space(1) },
});
