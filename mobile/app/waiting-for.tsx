import { entryCopy } from '@add/shared';
import { router } from 'expo-router';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';
import { Entry } from '@/components/entry';

const copy = entryCopy.waitingFor;

export default function WaitingFor() {
  const { token } = useSession();

  return (
    <Entry
      question={copy.question}
      meta={copy.meta}
      placeholder={copy.placeholder}
      label={copy.label}
      second={{ placeholder: copy.notePlaceholder, label: copy.noteLabel }}
      onSave={async (subject, note) => {
        await api.createWaitingFor(token as string, subject, note);
        router.back();
      }}
    />
  );
}
