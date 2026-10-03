import type { CommitmentProvenance } from '@add/shared';
import { commitmentProvenanceLabels, commitmentResponses } from '@add/shared';
import { Text, View } from 'react-native';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';
import { Responses } from '@/components/responses';
import { Meta } from '@/components/screen';
import { makeStyles } from '@/theme';

const useStyles = makeStyles(({ colors, type, space }) => ({
  row: { gap: space(1) },
  line: { ...type.body, color: colors.ink },
}));

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
  const styles = useStyles();

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
