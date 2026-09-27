import { router } from 'expo-router';
import { Button } from '@/components/button';
import { Meta, OneThing, Screen } from '@/components/screen';

/** The less common ways in, kept off home so home stays one thing. */
export default function Add() {
  return (
    <Screen>
      <OneThing>What is it?</OneThing>
      <Meta>a thought goes in capture; these are the other kinds</Meta>

      <Button label="Waiting on someone" onPress={() => router.replace('/waiting-for')} />
      <Button label="I said I'd do something" onPress={() => router.replace('/commitment')} />
      <Button label="Remind future me" onPress={() => router.replace('/remind')} />
      <Button label="Paste something that arrived" onPress={() => router.replace('/paste')} />
      <Button label="Not now" onPress={() => router.back()} />
    </Screen>
  );
}
