import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { login, register } from '@/routes';

export default function Welcome() {
    return (
        <>
            <Head title="add" />

            <div className="bg-background text-foreground flex min-h-screen flex-col px-6 py-10 sm:px-12">
                <p className="text-muted-foreground font-mono text-[11px] tracking-[0.18em] uppercase">
                    add
                </p>

                <div className="flex flex-1 items-center">
                    <div className="max-w-xl space-y-8">
                        <h1 className="border-now border-l-2 pl-5 text-[clamp(2rem,5.5vw,3.4rem)] leading-[1.06] font-medium tracking-[-0.02em] text-balance">
                            You should never have to work out what to do next.
                        </h1>

                        <p className="text-muted-foreground max-w-md pl-5">
                            Write the thought down however it comes out. add
                            turns it into steps, then shows you one of them at a
                            time, with the reason it picked that one.
                        </p>

                        <div className="flex gap-3 pl-5">
                            <Button asChild>
                                <Link href={register()}>Create an account</Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={login()}>Log in</Link>
                            </Button>
                        </div>
                    </div>
                </div>

                <p className="text-muted-foreground font-mono text-[11px] tracking-[0.12em]">
                    one thing at a time · skip weighs what done weighs
                </p>
            </div>
        </>
    );
}
