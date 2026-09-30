import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Wordmark } from '@/components/wordmark';
import { login, register } from '@/routes';

export default function Welcome() {
    return (
        <>
            <Head title="add" />

            <div className="bg-background text-foreground flex min-h-screen flex-col px-6 py-10 sm:px-12">
                <Wordmark />

                <div className="flex flex-1 items-center">
                    <div className="max-w-xl space-y-8">
                        <h1 className="text-one-thing text-balance">
                            You should never have to work out what to do next.
                        </h1>

                        <p className="text-muted-foreground text-lead max-w-md">
                            Write the thought down however it comes out. add
                            turns it into steps, then shows you one of them at a
                            time, with the reason it picked that one.
                        </p>

                        <div className="flex flex-wrap gap-3">
                            <Button asChild size="action">
                                <Link href={register()}>Create an account</Link>
                            </Button>
                            <Button asChild size="action" variant="outline">
                                <Link href={login()}>Log in</Link>
                            </Button>
                        </div>
                    </div>
                </div>

                <p className="text-muted-foreground">
                    one thing at a time · skip weighs what done weighs
                </p>
            </div>
        </>
    );
}
