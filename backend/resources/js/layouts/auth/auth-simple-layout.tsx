import { Wordmark } from '@/components/wordmark';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Wordmark />

                        <div className="space-y-2 text-center">
                            <h1 className="text-one-thing text-balance">
                                {title}
                            </h1>
                            <p className="text-muted-foreground text-lead text-center">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
