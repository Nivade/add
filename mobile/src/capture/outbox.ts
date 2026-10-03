import type { CaptureSource } from '@add/shared';
import { File, Paths } from 'expo-file-system';
import { useEffect } from 'react';
import { AppState } from 'react-native';
import { api } from '@/api/endpoints';
import { useSession } from '@/auth/session';

type Kept = { body: string; source: CaptureSource };

const file = new File(Paths.document, 'capture-outbox.json');

/** Every read and write of the file queues behind the one before it. */
let turn: Promise<unknown> = Promise.resolve();

function inTurn<T>(work: () => Promise<T>): Promise<T> {
  const next = turn.then(work, work);

  turn = next.catch(() => undefined);

  return next;
}

async function read(): Promise<Kept[]> {
  return file.exists ? (JSON.parse(await file.text()) as Kept[]) : [];
}

function write(kept: Kept[]): void {
  if (!file.exists) {
    file.create();
  }

  file.write(JSON.stringify(kept));
}

/** Only a request that never got an answer is kept; an answer, even an error, is the server's word. */
export function wasNeverAnswered(error: unknown): boolean {
  return error instanceof TypeError;
}

export function keepCapture(body: string, source: CaptureSource): Promise<void> {
  return inTurn(async () => write([...(await read()), { body, source }]));
}

/** A capture the server refused stays on the phone: the person's words are never dropped. */
export function sendKeptCaptures(token: string): Promise<void> {
  return inTurn(async () => {
    const left: Kept[] = [];
    const kept = await read();

    for (const [index, capture] of kept.entries()) {
      try {
        await api.capture(token, capture.body, capture.source);
      } catch (error) {
        left.push(capture);

        if (wasNeverAnswered(error)) {
          left.push(...kept.slice(index + 1));

          break;
        }
      }

      write([...left, ...kept.slice(index + 1)]);
    }
  });
}

/** Sends what was kept when the screen mounts and each time the app comes back to the foreground. */
export function useSendKeptCaptures(): void {
  const { token } = useSession();

  useEffect(() => {
    if (!token) {
      return;
    }

    const send = () => void sendKeptCaptures(token).catch(() => undefined);

    send();

    const subscription = AppState.addEventListener('change', (state) => {
      if (state === 'active') {
        send();
      }
    });

    return () => subscription.remove();
  }, [token]);
}
