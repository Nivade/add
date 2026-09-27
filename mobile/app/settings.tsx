import { useCallback, useState } from 'react';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Loading, Meta, OneThing, Screen } from '@/components/screen';
import { StaleNote, Unreachable } from '@/components/unreachable';

export default function Settings() {
  const { token } = useSession();
  const load = useCallback(() => api.aiConsent(token as string), [token]);
  const { data, loading, problem, reload, replace } = useResource(load);
  const [saving, setSaving] = useState(false);

  if (loading && !data) {
    return <Loading />;
  }

  if (!data) {
    return <Unreachable problem={problem} onRetry={() => void reload()} />;
  }

  const toggle = async () => {
    setSaving(true);

    try {
      replace(await api.updateAiConsent(token as string, !data.consented));
    } finally {
      setSaving(false);
    }
  };

  return (
    <Screen>
      <StaleNote problem={problem} />
      <OneThing>AI</OneThing>
      <Meta>
        {data.consented
          ? "A model outside this server can read what you capture."
          : 'Nothing you write leaves this server.'}
      </Meta>
      <Button
        label={data.consented ? 'Turn off' : 'Turn on'}
        onPress={() => void toggle()}
        disabled={saving}
      />
    </Screen>
  );
}
