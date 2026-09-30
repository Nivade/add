import type { ReactNode } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

/** Shared by every text input a person types into, so they stay visually identical. */
export const captureFieldClassName =
    'border-input focus-visible:border-ring focus-visible:ring-ring/50 text-body min-h-11 w-full rounded-field border bg-surface px-3 py-2 outline-none focus-visible:ring-[3px]';

export function CaptureDialog({
    title,
    description,
    open,
    onOpenChange,
    onCloseAutoFocus,
    children,
}: {
    title: string;
    description: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onCloseAutoFocus?: (event: Event) => void;
    children: ReactNode;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent onCloseAutoFocus={onCloseAutoFocus}>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                {children}
            </DialogContent>
        </Dialog>
    );
}
