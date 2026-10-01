import type { ExecutionStateData } from '@add/shared';
import { focusCopy, returnCopy, stepMeta, stuckReasonsFor } from '@add/shared';
import { Head, router } from '@inertiajs/react';
import { useId, useLayoutEffect, useState } from 'react';
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
    stepId,
    onClick,
}: {
    label: string;
    hint: string;
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
    const hintId = useId();
    const content = (
        <>
            <span className="flex items-baseline gap-2">
                {label}
                <KeyHint>{keys[0]}</KeyHint>
            </span>
            <span
                id={hintId}
                className="text-small text-muted-foreground hidden font-normal sm:block"
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
        <OneTapForm form={form} stepId={stepId}>
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
    const { session, intention, progress, elapsed } = state;
    const step = session.currentStep;
    const paused = session.pausedAt !== null;

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
                        <div
                            key={step?.id}
                            className="animate-in fade-in slide-in-from-bottom-2 flex flex-col items-start gap-4 duration-300 motion-reduce:animate-none"
                        >
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

                        <ControlRow label={focusCopy.thisStep}>
                            <Control
                                label={focusCopy.done}
                                hint={focusCopy.hints.done}
                                keys={['d']}
                                form={focusRoutes.completeStep.form(session.id)}
                                stepId={step?.id}
                            />
                            <Control
                                label={focusCopy.skip}
                                hint={focusCopy.hints.skip}
                                keys={['s']}
                                form={focusRoutes.skipStep.form(session.id)}
                                stepId={step?.id}
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
                                stepId={step?.id}
                            />
                            <Control
                                label={focusCopy.distracted}
                                hint={focusCopy.hints.distracted}
                                keys={['r']}
                                form={focusRoutes.distracted.form(session.id)}
                                stepId={step?.id}
                            />
                            <Control
                                label={focusCopy.stop}
                                hint={focusCopy.hints.stop}
                                keys={['x']}
                                form={focusRoutes.stop.form(session.id)}
                                stepId={step?.id}
                            />
                        </ControlRow>
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
