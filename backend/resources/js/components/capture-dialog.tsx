import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { RouteFormDefinition } from '@/wayfinder';

/** Shared by every text input across the capture dialogs, so they stay visually identical. */
export const captureFieldClassName =
    'border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-base outline-none focus-visible:ring-[3px]';

/** The trigger button + dialog shell every capture flow shares; the form body is the only thing that differs between them. */
export function CaptureDialog({
    trigger,
    title,
    description,
    open,
    onOpenChange,
    children,
}: {
    trigger: ReactNode;
    title: string;
    description: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    children: ReactNode;
}) {
    return (
        <>
            <Button
                variant="outline"
                size="sm"
                onClick={() => onOpenChange(true)}
            >
                {trigger}
            </Button>

            <Dialog open={open} onOpenChange={onOpenChange}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </DialogHeader>
                    {children}
                </DialogContent>
            </Dialog>
        </>
    );
}

/** A capture dialog whose whole body is one form: it closes and clears itself once the save lands. */
export function CaptureFormDialog({
    trigger,
    title,
    description,
    form,
    children,
}: {
    trigger: ReactNode;
    title: string;
    description: string;
    form: RouteFormDefinition<'post'>;
    children: (errors: Record<string, string | undefined>) => ReactNode;
}) {
    const [open, setOpen] = useState(false);

    return (
        <CaptureDialog
            trigger={trigger}
            title={title}
            description={description}
            open={open}
            onOpenChange={setOpen}
        >
            <Form
                {...form}
                options={{ preserveScroll: true }}
                onSuccess={() => setOpen(false)}
                resetOnSuccess
                className="flex flex-col gap-3"
            >
                {({ errors, processing }) => (
                    <>
                        {children(errors)}
                        <Button
                            type="submit"
                            disabled={processing}
                            className="self-end"
                        >
                            Save
                        </Button>
                    </>
                )}
            </Form>
        </CaptureDialog>
    );
}
