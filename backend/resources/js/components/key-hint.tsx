import { cn } from '@/lib/utils';

/** The key that does what the button does, shown on the button so it is learned by looking. */
export function KeyHint({
    children,
    className,
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <kbd
            aria-hidden="true"
            className={cn(
                'text-small font-normal pointer-coarse:hidden',
                className,
            )}
        >
            {children}
        </kbd>
    );
}
