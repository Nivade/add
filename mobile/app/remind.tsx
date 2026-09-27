import { router } from 'expo-router';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';
import { Entry } from '@/components/entry';

export default function Remind() {
  const { token } = useSession();

  return (
    <Entry
      question="What should future you hear, and when?"
      meta="say the time in the sentence"
      placeholder="Tomorrow at 5, buy dishwasher tablets"
      label="Reminder for later, with its time"
      onSave={async (text) => {
        await api.remindFutureSelf(token as string, text);
        router.back();
      }}
    />
  );
}
