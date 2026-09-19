import type { ExecutionStateData, StuckReason } from '@add/shared';
import { formatEstimate } from '@add/shared';
import { Form, Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import focusRoutes from '@/routes/focus';

const stuckReasons: { value: StuckReason; label: string }[] = [
    { value: 'dont_know_what_to_do', label: "I don't know what to do" },
    { value: 'too_big', label: 'This is too much' },
    { value: 'need_something', label: 'I need something' },
    {
        value: 'not_enough_information',
        label: "I don't have enough information",
    },
    { value: 'tired', label: "I'm tired" },
    { value: 'dont_want_to', label: "I don't want to do it" },
    { value: 'something_else', label: 'Something else' },
];

/** Six controls, one size, one weight: skipping weighs what finishing weighs. */
function Control({
    label,
    form,
}: {
    label: string;
    form: { action: string; method: 'post' };
}) {
    return (
        <Form {...form}>
            <Button
                type="submit"
                variant="outline"
                className="h-12 w-full font-mono text-[12px] tracking-[0.08em] uppercase"
            >
                {label}
            </Button>
        </Form>
    );
}

export default function Focus({ state }: { state: ExecutionStateData }) {
    const [stuckOpen, setStuckOpen] = useState(false);
    const { session, intention, progress } = state;
    const step = session.currentStep;
    const estimate = formatEstimate(step?.estimatedSeconds ?? null);
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
                        <h1 className="border-now border-l-2 pl-5 text-[clamp(1.8rem,4.6vw,2.9rem)] leading-[1.08] font-medium tracking-[-0.02em]">
                            Welcome back.
                        </h1>
                        <p className="text-muted-foreground pl-5 font-mono text-[13px]">
                            you left off at{' '}
                            {(step?.title ?? intention.title).toLowerCase()}
                        </p>
                        <div className="pl-5">
                            <Form {...focusRoutes.resume.form(session.id)}>
                                <Button type="submit">Continue</Button>
                            </Form>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="space-y-4">
                            <h1 className="border-now border-l-2 pl-5 text-[clamp(1.8rem,4.6vw,2.9rem)] leading-[1.08] font-medium tracking-[-0.02em] text-balance">
                                {step?.title}
                            </h1>
                            <p className="text-muted-foreground pl-5 font-mono text-[13px]">
                                {estimate ? `~${estimate}` : 'unestimated'}
                            </p>
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
                            <Button
                                type="button"
                                variant="outline"
                                className="h-12 w-full font-mono text-[12px] tracking-[0.08em] uppercase"
                                onClick={() => setStuckOpen(true)}
                            >
                                I'm stuck
                            </Button>
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

                {progress.length > 0 && (
                    <ul className="text-muted-foreground border-border space-y-1 border-t pt-5 font-mono text-[13px]">
                        {progress.map((line) => (
                            <li key={line}>{line.toLowerCase()}</li>
                        ))}
                    </ul>
                )}
            </div>

            <Dialog open={stuckOpen} onOpenChange={setStuckOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>What's blocking you?</DialogTitle>
                        <DialogDescription>
                            Every answer leads somewhere. None of them is a
                            failure.
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
