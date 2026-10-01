import type { ExecutionStateData } from '@add/shared';
import { focusCopy, returnCopy, stepMeta, stuckReasonsFor } from '@add/shared';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { KeyHint } from '@/components/key-hint';
import { NowButton } from '@/components/now-button';
import { Meta, OneThing } from '@/components/one-thing';
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
import focusRoutes from '@/routes/focus';

/** Six controls, one size, one weight: skipping weighs what finishing weighs. */
function Control({
    label,
    keys,
    form,
    stepId,
    onClick,
}: {
    label: string;
    keys: string[];
    form?: { action: string; method: 'post' };
    stepId?: string;
    onClick?: () => void;
}) {
    const ref = useShortcuts<HTMLButtonElement>(
        Object.fromEntries(
            keys.map((key) => [key, () => ref.current?.click()]),
        ),
    );
    const content = (
        <>
            {label}
            <KeyHint>{keys[0]}</KeyHint>
        </>
    );

    if (!form) {
        return (
            <Button
                ref={ref}
                type="button"
                variant="outline"
                size="control"
                className="w-full"
                aria-keyshortcuts={keys.join(' ')}
                onClick={onClick}
            >
                {content}
            </Button>
        );
    }

    return (
        <OneTapForm form={form} stepId={stepId}>
            {(processing) => (
                <Button
                    ref={ref}
                    type="submit"
                    variant="outline"
                    size="control"
                    className="w-full"
                    aria-keyshortcuts={keys.join(' ')}
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
    const { session, intention, progress, elapsed } = state;
    const step = session.currentStep;
    const paused = session.pausedAt !== null;

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
                            stepId={step?.id}
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
                        <div className="flex flex-col items-start gap-4">
                            <OneThing>{step?.title}</OneThing>
                            {step && <Meta>{stepMeta(step)}</Meta>}
                            {step && (
                                <SaidIdDoThis
                                    promised={state.currentStepIsCommitment}
                                    form={focusRoutes.commitment.form(
                                        session.id,
                                    )}
                                />
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <Control
                                label={focusCopy.done}
                                keys={['d']}
                                form={focusRoutes.completeStep.form(session.id)}
                                stepId={step?.id}
                            />
                            <Control
                                label={focusCopy.skip}
                                keys={['s']}
                                form={focusRoutes.skipStep.form(session.id)}
                                stepId={step?.id}
                            />
                            <Control
                                label={focusCopy.pause}
                                keys={['p']}
                                form={focusRoutes.pause.form(session.id)}
                                stepId={step?.id}
                            />
                            <Control
                                label={focusCopy.stuck}
                                keys={['?', 'h']}
                                onClick={() => setStuckOpen(true)}
                            />
                            <Control
                                label={focusCopy.distracted}
                                keys={['r']}
                                form={focusRoutes.distracted.form(session.id)}
                                stepId={step?.id}
                            />
                            <Control
                                label={focusCopy.stop}
                                keys={['x']}
                                form={focusRoutes.stop.form(session.id)}
                                stepId={step?.id}
                            />
                        </div>
                    </>
                )}

                <ul className="text-muted-foreground space-y-1">
                    <li>{elapsed.toLowerCase()}</li>
                    {progress.map((line) => (
                        <li key={line}>{line.toLowerCase()}</li>
                    ))}
                </ul>
            </div>

            <Dialog open={stuckOpen} onOpenChange={setStuckOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{focusCopy.stuckQuestion}</DialogTitle>
                        <DialogDescription>
                            {focusCopy.stuckMeta}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex flex-col gap-2">
                        {stuckReasonsFor(step?.place ?? null).map(
                            (reason, index) => (
                                <StuckReason
                                    key={reason.value}
                                    label={reason.label}
                                    shortcut={String(index + 1)}
                                    onChoose={() => {
                                        setStuckOpen(false);
                                        router.post(
                                            focusRoutes.stuck.url(session.id),
                                            {
                                                step_id: step?.id,
                                                reason: reason.value,
                                            },
                                        );
                                    }}
                                />
                            ),
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
