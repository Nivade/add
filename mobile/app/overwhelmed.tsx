import type { OverwhelmedData } from '@add/shared';
import { homeCopy, restCountLine, stepMeta } from '@add/shared';
import { router } from 'expo-router';
import { useCallback } from 'react';
import { api } from '@/api/endpoints';
import { useResource } from '@/api/use-resource';
import { useSession } from '@/auth/session';
import { Button } from '@/components/button';
import { Meta, OneThing, Screen } from '@/components/screen';
import { Pending, StaleNote } from '@/components/resource-state';

/** No bands, no capture, one way back: this screen exists to remove everything else. */
export default function Overwhelmed() {
  const { token } = useSession();
  const load = useCallback(() => api.overwhelmed(token as string), [token]);
  const resource = useResource<OverwhelmedData>(load);

  if (resource.status !== 'ready') {
    return <Pending resource={resource} />;
  }

  const { data, problem } = resource;

  const step = data.smallestStep;

  const start = async () => {
    if (!step) {
      return;
    }

    await api.startSession(token as string, step.step.id);
    router.replace('/focus');
  };

  return (
    <Screen>
      <StaleNote problem={problem} />
      {step ? (
        <>
          <OneThing>{step.step.title}</OneThing>
          <Meta>
            {stepMeta(step.step)} · that is all you have to do
          </Meta>
          {step.why.map((line) => (
            <Meta key={line}>{line}</Meta>
          ))}
          <Button label="Start" tone="primary" onPress={() => void start()} />
        </>
      ) : (
        <OneThing>{homeCopy.nothingNeedsYou}</OneThing>
      )}

      <Meta>{restCountLine(data.restCount)}</Meta>

      <Button label="Back" onPress={() => router.replace('/')} />
    </Screen>
  );
}
