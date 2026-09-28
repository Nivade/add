import { router } from 'expo-router';
import type { Resource } from '@/api/use-resource';
import { Button } from '@/components/button';
import { Loading, Meta, OneThing, Screen } from '@/components/screen';

/** Everything a screen shows before it has an answer: the wait, or the problem with a way to retry. */
export function Pending<T>({ resource }: { resource: Resource<T> & { status: 'loading' | 'unreachable' } }) {
  if (resource.status === 'loading') {
    return <Loading />;
  }

  return (
    <Screen>
      <OneThing>{resource.problem}</OneThing>
      <Button label="Try again" onPress={() => void resource.reload()} />
      {router.canGoBack() && <Button label="Back" onPress={() => router.back()} />}
    </Screen>
  );
}

/** A refresh that failed keeps what the screen showed, and says it may be behind. */
export function StaleNote({ problem }: { problem: string | null }) {
  return problem ? <Meta>{problem} What is here may be out of date.</Meta> : null;
}
