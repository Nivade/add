import { aiConsentCopy } from '@add/shared';
import { useCallback, useState } from 'react';
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
        {aiConsentCopy(data.consented).line}
      </Meta>
      <Button
        label={aiConsentCopy(data.consented).action}
        onPress={() => void toggle()}
        disabled={saving}
      />
    </Screen>
  );
}
