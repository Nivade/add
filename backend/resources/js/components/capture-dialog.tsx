import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

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
            <Button variant="outline" size="sm" onClick={() => onOpenChange(true)}>
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
