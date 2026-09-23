import type { ExecutionStateData } from '@add/shared';
import { stuckReasons } from '@add/shared';
import { Form, Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Meta, OneThing, stepMeta } from '@/components/one-thing';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import focusRoutes from '@/routes/focus';

const CONTROL_CLASS =
    'h-12 w-full font-mono text-[12px] tracking-[0.08em] uppercase';

/** Six controls, one size, one weight: skipping weighs what finishing weighs. */
function Control({
    label,
    form,
    onClick,
}: {
    label: string;
    form?: { action: string; method: 'post' };
    onClick?: () => void;
}) {
    const button = (
        <Button
            type={form ? 'submit' : 'button'}
            variant="outline"
            className={CONTROL_CLASS}
            onClick={onClick}
        >
            {label}
        </Button>
    );

    return form ? <Form {...form}>{button}</Form> : button;
}

export default function Focus({ state }: { state: ExecutionStateData }) {
    const [stuckOpen, setStuckOpen] = useState(false);
    const { session, intention, progress, elapsed } = state;
    const step = session.currentStep;
    const paused = session.pausedAt !== null;

    return (
        <>
            <Head title={intention.title} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-8 px-6 pt-6 pb-20 lg:mx-0 lg:ml-[8vw]">
                <p className="text-muted-foreground font-mono text-[11px] tracking-[0.18em] uppercase">
                    {intention.title}
                </p>

                {paused ? (
                    <div className="space-y-6">
                        <OneThing>Welcome back.</OneThing>
                        <Meta>
                            you left off at{' '}
                            {(step?.title ?? intention.title).toLowerCase()}
                        </Meta>
                        <div className="pl-5">
                            <Form {...focusRoutes.resume.form(session.id)}>
                                <Button type="submit">Continue</Button>
                            </Form>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="space-y-4">
                            <OneThing>{step?.title}</OneThing>
                            {step && <Meta>{stepMeta(step)}</Meta>}
                        </div>

                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            <Control
                                label="Done"
                                form={focusRoutes.completeStep.form(session.id)}
                            />
                            <Control
                                label="Skip"
                                form={focusRoutes.skipStep.form(session.id)}
                            />
                            <Control
                                label="Pause"
                                form={focusRoutes.pause.form(session.id)}
                            />
                            <Control
                                label="I'm stuck"
                                onClick={() => setStuckOpen(true)}
                            />
                            <Control
                                label="I got distracted"
                                form={focusRoutes.distracted.form(session.id)}
                            />
                            <Control
                                label="Stop"
                                form={focusRoutes.stop.form(session.id)}
                            />
                        </div>
                    </>
                )}

                <ul className="text-muted-foreground border-border space-y-1 border-t pt-5 font-mono text-[13px]">
                    <li>{elapsed.toLowerCase()}</li>
                    {progress.map((line) => (
                        <li key={line}>{line.toLowerCase()}</li>
                    ))}
                </ul>
            </div>

            <Dialog open={stuckOpen} onOpenChange={setStuckOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>What's blocking you?</DialogTitle>
                        <DialogDescription>
                            Every answer leads somewhere.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex flex-col gap-2">
                        {stuckReasons.map((reason) => (
                            <Button
                                key={reason.value}
                                variant="outline"
                                className="h-11 justify-start"
                                onClick={() => {
                                    setStuckOpen(false);
                                    router.post(
                                        focusRoutes.stuck.url(session.id),
                                        { reason: reason.value },
                                    );
                                }}
                            >
                                {reason.label}
                            </Button>
                        ))}
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
