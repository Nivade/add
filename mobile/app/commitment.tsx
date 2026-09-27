import { entryCopy } from '@add/shared';
import { router } from 'expo-router';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';
import { Entry } from '@/components/entry';

const copy = entryCopy.commitment;

export default function Commitment() {
  const { token } = useSession();

  return (
    <Entry
      question={copy.question}
      meta={copy.meta}
      placeholder={copy.placeholder}
      label={copy.label}
      onSave={async (text) => {
        await api.createCommitment(token as string, text);
        router.back();
      }}
    />
  );
}
