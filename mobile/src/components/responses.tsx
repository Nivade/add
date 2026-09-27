import { useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { Button } from '@/components/button';
import { theme } from '@/theme';

/** Every answer weighs the same, and one tap at a time: the row locks until the server answers. */
export function Responses<T extends string>({
  responses,
  onRespond,
}: {
  responses: { value: T; label: string }[];
  onRespond: (value: T) => Promise<void>;
}) {
  const [saving, setSaving] = useState(false);

  const respond = async (value: T) => {
    setSaving(true);

    try {
      await onRespond(value);
    } finally {
      setSaving(false);
    }
  };

  return (
    <View style={styles.row}>
      {responses.map(({ value, label }) => (
        <Button
          key={value}
          label={label}
          disabled={saving}
          onPress={() => void respond(value)}
        />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: theme.space(1) },
});
