import { KeyHint } from '@/components/key-hint';
import { Button } from '@/components/ui/button';
import { useShortcuts } from '@/hooks/use-shortcuts';

/** Start and Continue, and nothing else: the one filled button on a screen, answered by Enter. */
export function NowButton({
    children,
    ...props
}: Omit<React.ComponentProps<typeof Button>, 'variant' | 'size' | 'ref'>) {
    const ref = useShortcuts<HTMLButtonElement>({
        Enter: () => ref.current?.click(),
    });

    return (
        <Button
            ref={ref}
            variant="now"
            size="action"
            aria-keyshortcuts="Enter"
            {...props}
        >
            {children}
            <KeyHint>↵</KeyHint>
        </Button>
    );
}
