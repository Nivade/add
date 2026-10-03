import type { CaptureKind, SortedCaptureData } from '@add/shared';
import {
  captureKindChoices,
  homeBands,
  sortedCopy,
  sortedLine,
  sortedMoreLine,
} from '@add/shared';
import { useState } from 'react';
import { Text, View } from 'react-native';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Responses } from '@/components/responses';
import { Band, Meta } from '@/components/screen';
import { makeStyles } from '@/theme';

const useStyles = makeStyles(({ colors, type, space }) => ({
  list: { gap: space(3) },
  item: { gap: space(1) },
  line: { ...type.body, color: colors.ink },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: space(1) },
}));

function SortedItem({ item, onChanged }: { item: SortedCaptureData; onChanged: () => void }) {
  const { token } = useSession();
  const styles = useStyles();
  const [choosing, setChoosing] = useState(false);
  const notForYou = item.kind === 'not_for_you';

  const change = async (kind: CaptureKind) => {
    await api.changeCaptureKind(token as string, item.id, kind);
    onChanged();
  };

  return (
    <View style={styles.item}>
      <Text style={styles.line}>“{item.excerpt}”</Text>
      <Meta>{sortedLine(item.kind, item.detail)}</Meta>
      <View style={styles.row}>
        {notForYou && (
          <Responses
            responses={[{ value: 'thought', label: sortedCopy.keep }]}
            onRespond={change}
          />
        )}
        <Responses
          responses={[{ value: 'confirm', label: sortedCopy.right }]}
          onRespond={async () => {
            await api.confirmCaptureKind(token as string, item.id);
            onChanged();
          }}
        />
        {!notForYou && (
          <Button label={sortedCopy.notRight} onPress={() => setChoosing(!choosing)} />
        )}
      </View>
      {choosing && (
        <Responses
          responses={captureKindChoices.filter(({ value }) => value !== item.kind)}
          onRespond={change}
        />
      )}
    </View>
  );
}

/** Everything the app sorted is read back until the person says it is right, or changes it. */
export function SortedBand({
  sorted,
  more,
  onChanged,
}: {
  sorted: SortedCaptureData[];
  more: number;
  onChanged: () => void;
}) {
  const styles = useStyles();

  if (sorted.length === 0) {
    return null;
  }

  return (
    <Band label={homeBands.sorted}>
      <View style={styles.list}>
        {sorted.map((item) => (
          <SortedItem key={item.id} item={item} onChanged={onChanged} />
        ))}
      </View>
      {more > 0 && <Meta>{sortedMoreLine(more)}</Meta>}
    </Band>
  );
}
