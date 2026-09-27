import type { CommitmentProvenance } from '@add/shared';
import { commitmentProvenanceLabels, commitmentResponses } from '@add/shared';
import { StyleSheet, Text, View } from 'react-native';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';
import { Responses } from '@/components/responses';
import { Meta } from '@/components/screen';
import { line, theme } from '@/theme';

/** One commitment and its answers, the same on home's band and on the full list. */
export function CommitmentRow({
  id,
  description,
  provenance,
  awaitingConfirmation,
  onResponded,
}: {
  id: string;
  description: string;
  provenance: CommitmentProvenance | null;
  awaitingConfirmation: boolean;
  onResponded: () => void;
}) {
  const { token } = useSession();

  return (
    <View style={styles.row}>
      <Text style={styles.line}>{description}</Text>
      {provenance && <Meta>{commitmentProvenanceLabels[provenance]}</Meta>}
      <Responses
        responses={commitmentResponses(awaitingConfirmation)}
        onRespond={async (response) => {
          await api.respondToCommitment(token as string, id, response);
          onResponded();
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  row: { gap: theme.space(1) },
  line,
});
