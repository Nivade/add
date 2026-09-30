import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { typeScale } from '@add/shared';
import { extendTailwindMerge } from 'tailwind-merge';

/** Without this, `text-small` reads as a colour and silently wins over `text-now`. */
const twMerge = extendTailwindMerge({
    extend: {
        theme: {
            text: Object.keys(typeScale).map((role) =>
                role.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`),
            ),
        },
    },
});

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}
