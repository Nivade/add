import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

export function Wordmark({ className }: { className?: string }) {
    return (
        <Link
            href={home()}
            className={cn(
                'text-ink text-lead inline-flex min-h-11 items-center font-bold',
                className,
            )}
        >
            add
        </Link>
    );
}
