import { router } from 'expo-router';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';
import { Entry } from '@/components/entry';

export default function Commitment() {
  const { token } = useSession();

  return (
    <Entry
      question="What did you just commit to?"
      meta="said out loud or typed, it counts the same"
      placeholder="I'll call Sarah Friday"
      label="What you committed to"
      onSave={async (description) => {
        await api.createCommitment(token as string, description);
        router.back();
      }}
    />
  );
}
