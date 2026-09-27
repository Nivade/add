import { router } from 'expo-router';
import { Button } from '@/components/button';
import { Meta, OneThing, Screen } from '@/components/screen';

/** A first load that failed: nothing to show, so the problem is the one thing and retrying is the way on. */
export function Unreachable({ problem, onRetry }: { problem: string | null; onRetry: () => void }) {
  return (
    <Screen>
      <OneThing>{problem ?? 'The app could not reach the server.'}</OneThing>
      <Button label="Try again" onPress={onRetry} />
      {router.canGoBack() && <Button label="Back" onPress={() => router.back()} />}
    </Screen>
  );
}

/** A refresh that failed keeps what the screen showed, and says it may be behind. */
export function StaleNote({ problem }: { problem: string | null }) {
  return problem ? <Meta>{problem} What is here may be out of date.</Meta> : null;
}
