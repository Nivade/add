import type {
    ExecutionStateData,
    StuckReason as StuckReasonValue,
} from '@add/shared';
import { focusCopy, returnCopy, stuckReasonsFor } from '@add/shared';
import { Head, router } from '@inertiajs/react';
import { useId, useLayoutEffect, useState } from 'react';
import { KeyHint } from '@/components/key-hint';
import { NowButton } from '@/components/now-button';
import {
    EstimateLine,
    Meta,
    OneThing,
    SuggestedPill,
} from '@/components/one-thing';
import { OneTapForm } from '@/components/one-tap-form';
import { SaidIdDoThis } from '@/components/said-id-do-this';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useShortcuts } from '@/hooks/use-shortcuts';
import { fieldClassName } from '@/lib/field';
import focusRoutes from '@/routes/focus';

const controlClassName =
    'h-full min-h-16 w-full flex-col gap-0.5 py-2 whitespace-normal';

/** Three equal controls under a plain name for what they act on. */
function ControlRow({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    const id = useId();

    return (
        <div role="group" aria-labelledby={id} className="flex flex-col gap-2">
            <p id={id} className="text-muted-foreground text-small">
                {label}
            </p>
            <div className="grid grid-cols-3 gap-3">{children}</div>
        </div>
    );
}

/** Six controls, one size, one weight: skipping weighs what finishing weighs. */
function Control({
    label,
    hint,
    keys,
    form,
    fields = {},
    onClick,
}: {
    label: string;
    hint: string;
    keys: string[];
    form?: { action: string; method: 'post' };
    fields?: Record<string, string>;
    onClick?: () => void;
}) {
    const ref = useShortcuts<HTMLButtonElement>(
        Object.fromEntries(
            keys.map((key) => [key, () => ref.current?.click()]),
        ),
    );
    const hintId = useId();
    const content = (
        <>
            <span className="flex items-baseline gap-2">
                {label}
                <KeyHint>{keys[0]}</KeyHint>
            </span>
            <span
                id={hintId}
                className="text-small text-muted-foreground block font-normal"
            >
                {hint}
            </span>
        </>
    );

    if (!form) {
        return (
            <Button
                ref={ref}
                type="button"
                variant="outline"
                size="control"
                className={controlClassName}
                aria-keyshortcuts={keys.join(' ')}
                aria-label={label}
                aria-describedby={hintId}
                onClick={onClick}
            >
                {content}
            </Button>
        );
    }

    return (
        <OneTapForm form={form} fields={fields}>
            {(processing) => (
                <Button
                    ref={ref}
                    type="submit"
                    variant="outline"
                    size="control"
                    className={controlClassName}
                    aria-keyshortcuts={keys.join(' ')}
                    aria-label={label}
                    aria-describedby={hintId}
                    aria-disabled={processing}
                >
                    {content}
                </Button>
            )}
        </OneTapForm>
    );
}

function StuckReason({
    label,
    shortcut,
    onChoose,
}: {
    label: string;
    shortcut: string;
    onChoose: () => void;
}) {
    const ref = useShortcuts<HTMLButtonElement>({ [shortcut]: onChoose });

    return (
        <Button
            ref={ref}
            variant="outline"
            className="justify-between font-normal"
            aria-keyshortcuts={shortcut}
            onClick={onChoose}
        >
            {label}
            <KeyHint>{shortcut}</KeyHint>
        </Button>
    );
}

export default function Focus({ state }: { state: ExecutionStateData }) {
    const [stuckOpen, setStuckOpen] = useState(false);
    const [askingNote, setAskingNote] = useState(false);
    const { session, intention, progress, elapsed } = state;
    const step = session.currentStep;
    const paused = session.pausedAt !== null;
    const stepFields = { step_id: step?.id ?? '' };
    const seenFields = { seen_event_id: state.seenEventId };

    const reportStuck = (reason: StuckReasonValue, note?: string): void => {
        setStuckOpen(false);
        setAskingNote(false);
        router.post(focusRoutes.stuck.url(session.id), {
            step_id: step?.id ?? '',
            reason,
            note: note ?? '',
        });
    };

    useLayoutEffect(() => {
        document.querySelector<HTMLElement>('main h1')?.focus();
    }, [state]);

    return (
        <>
            <Head title={intention.title} />

            <div className="flex flex-col gap-10">
                <p className="text-muted-foreground">{intention.title}</p>

                {state.returning || paused ? (
                    <div className="flex flex-col items-start gap-5">
                        <OneThing>
                            {state.returning
                                ? returnCopy.welcome
                                : returnCopy.paused}
                        </OneThing>
                        <Meta>
                            {state.returning
                                ? returnCopy.meta(
                                      intention.title,
                                      state.stepsDone,
                                  )
                                : returnCopy.pausedMeta}
                        </Meta>
                        <OneTapForm
                            form={focusRoutes.resume.form(session.id)}
                            fields={seenFields}
                        >
                            {(processing) => (
                                <NowButton
                                    type="submit"
                                    aria-disabled={processing}
                                >
                                    {focusCopy.continue}
                                </NowButton>
                            )}
                        </OneTapForm>
                    </div>
                ) : (
                    <>
                        <div
                            key={step?.id}
                            className="animate-in fade-in slide-in-from-bottom-2 flex flex-col items-start gap-4 duration-300 motion-reduce:animate-none"
                        >
                            {state.notice && (
                                <p
                                    role="status"
                                    className="text-muted-foreground text-lead"
                                >
                                    {state.notice}
                                </p>
                            )}
                            <OneThing>{step?.title}</OneThing>
                            {step && (
                                <div className="flex flex-wrap items-center gap-3">
                                    <Meta>
                                        <EstimateLine
                                            seconds={step.estimatedSeconds}
                                            underWay
                                        />
                                    </Meta>
                                    {step.generated && <SuggestedPill />}
                                </div>
                            )}
                            {step && (
                                <SaidIdDoThis
                                    promised={state.currentStepIsCommitment}
                                    form={focusRoutes.commitment.form(
                                        session.id,
                                    )}
                                    fields={stepFields}
                                />
                            )}
                        </div>

                        <ControlRow label={focusCopy.thisStep}>
                            <Control
                                label={focusCopy.done}
                                hint={focusCopy.hints.done}
                                keys={['d']}
                                form={focusRoutes.completeStep.form(session.id)}
                                fields={stepFields}
                            />
                            <Control
                                label={focusCopy.skip}
                                hint={focusCopy.hints.skip}
                                keys={['s']}
                                form={focusRoutes.skipStep.form(session.id)}
                                fields={stepFields}
                            />
                            <Control
                                label={focusCopy.stuck}
                                hint={focusCopy.hints.stuck}
                                keys={['?', 'h']}
                                onClick={() => setStuckOpen(true)}
                            />
                        </ControlRow>
                        <ControlRow label={focusCopy.stepAway}>
                            <Control
                                label={focusCopy.pause}
                                hint={focusCopy.hints.pause}
                                keys={['p']}
                                form={focusRoutes.pause.form(session.id)}
                                fields={seenFields}
                            />
                            <Control
                                label={focusCopy.distracted}
                                hint={focusCopy.hints.distracted}
                                keys={['r']}
                                form={focusRoutes.distracted.form(session.id)}
                                fields={seenFields}
                            />
                            <Control
                                label={focusCopy.stop}
                                hint={focusCopy.hints.stop}
                                keys={['x']}
                                form={focusRoutes.stop.form(session.id)}
                                fields={seenFields}
                            />
                        </ControlRow>
                    </>
                )}

                <ul className="text-muted-foreground space-y-1">
                    <li>{elapsed}</li>
                    {progress.map((line) => (
                        <li key={line}>{line}</li>
                    ))}
                </ul>
            </div>

            <Dialog
                open={stuckOpen}
                onOpenChange={(open) => {
                    setStuckOpen(open);
                    setAskingNote(false);
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{focusCopy.stuckQuestion}</DialogTitle>
                        <DialogDescription>
                            {focusCopy.stuckMeta}
                        </DialogDescription>
                    </DialogHeader>
                    {askingNote ? (
                        <form
                            className="flex flex-col gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                const note = new FormData(
                                    event.currentTarget,
                                ).get('note');

                                reportStuck(
                                    'something_else',
                                    typeof note === 'string' ? note : '',
                                );
                            }}
                        >
                            <label htmlFor="stuck-note">
                                {focusCopy.stuckNoteQuestion}
                            </label>
                            <textarea
                                id="stuck-note"
                                name="note"
                                rows={3}
                                autoFocus
                                className={fieldClassName}
                            />
                            <div className="flex justify-end">
                                <Button type="submit">
                                    {focusCopy.stuckNoteSend}
                                </Button>
                            </div>
                        </form>
                    ) : (
                        <div className="flex flex-col gap-2">
                            {stuckReasonsFor(step?.place ?? null).map(
                                (reason, index) => (
                                    <StuckReason
                                        key={reason.value}
                                        label={reason.label}
                                        shortcut={String(index + 1)}
                                        onChoose={() =>
                                            reason.value === 'something_else'
                                                ? setAskingNote(true)
                                                : reportStuck(reason.value)
                                        }
                                    />
                                ),
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
