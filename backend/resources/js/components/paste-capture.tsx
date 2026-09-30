import type { IngestionClassificationData } from '@add/shared';
import { entryCopy } from '@add/shared';
import { Form, useHttp } from '@inertiajs/react';
import { useState } from 'react';
import {
    captureFieldClassName,
    CaptureDialog,
} from '@/components/capture-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { store } from '@/routes/captures';
import { classify } from '@/routes/ingestion';

function serverMessage(data: unknown): string | null {
    try {
        const body: unknown =
            typeof data === 'string' ? JSON.parse(data) : data;

        return typeof body === 'object' &&
            body !== null &&
            'message' in body &&
            typeof body.message === 'string'
            ? body.message
            : null;
    } catch {
        return null;
    }
}

/** A 422 already lands in `http.errors`; anything else gets one plain line. */
function failureMessage(error: unknown): string | null {
    const response =
        typeof error === 'object' && error !== null && 'response' in error
            ? (error.response as { status?: number; data?: unknown })
            : null;

    if (response?.status === 422) {
        return null;
    }

    return (
        serverMessage(response?.data) ??
        'That could not be read just now. Nothing was saved.'
    );
}

/** Nothing is fetched from anywhere: only what the person pastes here is read. */
export function PasteCapture() {
    const [open, setOpen] = useState(false);
    const [result, setResult] = useState<IngestionClassificationData | null>(
        null,
    );
    const [failure, setFailure] = useState<string | null>(null);
    const http = useHttp<{ text: string }, IngestionClassificationData>({
        text: '',
    });

    function close(next: boolean) {
        setOpen(next);

        if (!next) {
            http.reset();
            setResult(null);
            setFailure(null);
        }
    }

    function read(event: React.FormEvent) {
        event.preventDefault();
        setFailure(null);
        http.post(classify.url())
            .then(setResult)
            .catch((error: unknown) => setFailure(failureMessage(error)));
    }

    return (
        <CaptureDialog
            trigger="Paste"
            title={entryCopy.paste.question}
            description={entryCopy.paste.meta}
            open={open}
            onOpenChange={close}
        >
            {result === null ? (
                <form onSubmit={read} className="flex flex-col gap-3">
                    <textarea
                        name="text"
                        rows={6}
                        autoFocus
                        value={http.data.text}
                        onChange={(event) =>
                            http.setData('text', event.target.value)
                        }
                        placeholder={entryCopy.paste.placeholder}
                        aria-label={entryCopy.paste.label}
                        className={captureFieldClassName}
                    />
                    <InputError
                        message={http.errors.text ?? failure ?? undefined}
                    />
                    <Button
                        type="submit"
                        disabled={http.processing}
                        className="self-end"
                    >
                        Read it
                    </Button>
                </form>
            ) : (
                <Classification
                    result={result}
                    text={http.data.text}
                    onDone={() => close(false)}
                />
            )}
        </CaptureDialog>
    );
}

function Classification({
    result,
    text,
    onDone,
}: {
    result: IngestionClassificationData;
    text: string;
    onDone: () => void;
}) {
    if (!result.actionable) {
        return (
            <div className="flex flex-col gap-3">
                <p>{entryCopy.paste.nothingNeeded}</p>
                <Button variant="outline" onClick={onDone} className="self-end">
                    {entryCopy.paste.close}
                </Button>
            </div>
        );
    }

    return (
        <Form
            {...store.form()}
            transform={() => ({ body: text })}
            options={{ preserveScroll: true }}
            onSuccess={onDone}
            className="flex flex-col gap-3"
        >
            <p className="text-lg">{result.title}</p>
            {result.why && (
                <p className="text-muted-foreground">{result.why}</p>
            )}
            <div className="flex justify-end gap-3">
                <Button type="button" variant="outline" onClick={onDone}>
                    {entryCopy.paste.leave}
                </Button>
                <Button type="submit" variant="outline">
                    {entryCopy.paste.add}
                </Button>
            </div>
        </Form>
    );
}
