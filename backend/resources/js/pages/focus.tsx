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
import { focus } from '@/routes';
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
            <Button type="submit" variant="outline" className="h-11 w-full">
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

            <div className="mx-auto flex w-full max-w-xl flex-col gap-8 px-6 py-10">
                <p className="text-muted-foreground text-xs font-medium tracking-[0.18em] uppercase">
                    {intention.title}
                </p>

                {paused ? (
                    <div className="space-y-5">
                        <h1 className="border-now border-l-2 pl-4 text-[clamp(1.75rem,5vw,2.75rem)] leading-[1.1] font-semibold tracking-tight">
                            Welcome back.
                        </h1>
                        <p className="text-muted-foreground">
                            You left off at {step?.title ?? intention.title}
                        </p>
                        <Form {...focusRoutes.resume.form(session.id)}>
                            <Button type="submit" size="lg">
                                Continue
                            </Button>
                        </Form>
                    </div>
                ) : (
                    <>
                        <div className="space-y-3">
                            <h1 className="border-now border-l-2 pl-4 text-[clamp(1.75rem,5vw,2.75rem)] leading-[1.1] font-semibold tracking-tight text-balance">
                                {step?.title}
                            </h1>
                            {estimate && (
                                <p className="text-muted-foreground pl-4">
                                    About {estimate}.
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
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
                                className="h-11 w-full"
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
                    <ul className="text-muted-foreground border-border space-y-1 border-t pt-4 text-sm">
                        {progress.map((line) => (
                            <li key={line}>{line}</li>
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

Focus.layout = {
    breadcrumbs: [{ title: 'Focus', href: focus() }],
};
