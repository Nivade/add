import { useCallback, useState } from 'react';
import { deviceTimezone } from '@/api/client';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Meta, OneThing, Screen } from '@/components/screen';
import { Pending, StaleNote } from '@/components/resource-state';

export default function Settings() {
  const { token } = useSession();
  const load = useCallback(() => api.aiConsent(token as string), [token]);
  const resource = useResource(load);
  const [saving, setSaving] = useState(false);

  if (resource.status !== 'ready') {
    return <Pending resource={resource} />;
  }

  const { data, problem, replace } = resource;

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
      <Meta>
        Times are read in {deviceTimezone()}, taken from this device.
      </Meta>
    </Screen>
  );
}
