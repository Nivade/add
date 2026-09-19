import { Link, usePage } from '@inertiajs/react';
import { QuickCapture } from '@/components/quick-capture';
import { Rail } from '@/components/rail';
import { UserMenu } from '@/components/user-menu';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { focus, home } from '@/routes';

/** Two places to be, one key to capture, and a rail that says what time it is. Nothing else in the frame. */
export default function Shell({ children }: { children: React.ReactNode }) {
    const { rail } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const tabs = [
        { label: 'Home', href: home.url() },
        { label: 'Focus', href: focus.url() },
    ];

    return (
        <div className="bg-background text-foreground flex min-h-screen">
            {rail && <Rail rail={rail} />}

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="mx-auto flex w-full max-w-2xl items-center gap-6 px-6 py-5 lg:mx-0 lg:ml-[8vw] lg:max-w-[calc(100%-8vw)] lg:pr-10">
                    <nav className="flex gap-5" aria-label="Main">
                        {tabs.map((tab) => (
                            <Link
                                key={tab.href}
                                href={tab.href}
                                className={`font-mono text-[11px] tracking-[0.16em] uppercase transition-colors ${
                                    isCurrentOrParentUrl(tab.href)
                                        ? 'text-foreground'
                                        : 'text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                {tab.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-3">
                        <QuickCapture />
                        <UserMenu />
                    </div>
                </header>

                <main className="flex-1">{children}</main>
            </div>
        </div>
    );
}
