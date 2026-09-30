import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAi } from '@/routes/ai';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editCalendar } from '@/routes/calendar';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const settingsNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'Calendar',
        href: editCalendar(),
        icon: null,
    },
    {
        title: 'AI',
        href: editAi(),
        icon: null,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="flex flex-col gap-10">
            <div className="flex flex-col gap-2">
                <h1 className="text-one-thing">Settings</h1>

                <nav className="flex flex-wrap gap-x-6" aria-label="Settings">
                    {settingsNavItems.map((item) => {
                        const current = isCurrentOrParentUrl(item.href);

                        return (
                            <Link
                                key={toUrl(item.href)}
                                href={item.href}
                                aria-current={current ? 'page' : undefined}
                                className={cn(
                                    'inline-flex min-h-11 items-center underline-offset-4 hover:underline',
                                    current
                                        ? 'text-ink font-semibold'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {item.title}
                            </Link>
                        );
                    })}
                </nav>
            </div>

            <section className="space-y-12">{children}</section>
        </div>
    );
}
