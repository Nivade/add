import type { OverwhelmedData } from '@add/shared';
import { formatEstimate } from '@add/shared';
import { router } from 'expo-router';
import { useCallback } from 'react';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Loading, Meta, OneThing, Screen } from '@/components/screen';

/** No bands, no capture, one way back: this screen exists to remove everything else. */
export default function Overwhelmed() {
  const { token } = useSession();
  const load = useCallback(() => api.overwhelmed(token as string), [token]);
  const { data, loading } = useResource<OverwhelmedData>(load);

  if (loading && !data) {
    return <Loading />;
  }

  const step = data?.smallestStep;

  const start = async () => {
    if (!step) {
      return;
    }

    await api.startSession(token as string, step.step.id);
    router.replace('/focus');
  };

  return (
    <Screen>
      {step ? (
        <>
          <OneThing>{step.step.title}</OneThing>
          <Meta>
            {formatEstimate(step.step.estimatedSeconds) ?? 'a small one'} · that
            is all you have to do
          </Meta>
          <Button label="Start" tone="primary" onPress={() => void start()} />
        </>
      ) : (
        <>
          <OneThing>There is nothing small left.</OneThing>
          <Meta>that is allowed</Meta>
        </>
      )}

      <Button label="Back" onPress={() => router.replace('/')} />
    </Screen>
  );
}
