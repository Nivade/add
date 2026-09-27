import type { CommitmentListData } from '@add/shared';
import { commitmentCopy } from '@add/shared';
import { Head, Link } from '@inertiajs/react';
import { CommitmentRow } from '@/components/commitment-row';
import { home } from '@/routes';

/** Reached only from home; home stays the place things are chosen from. */
export default function Commitments({
    list: { commitments: open },
}: {
    list: CommitmentListData;
}) {
    return (
        <>
            <Head title={commitmentCopy.listTitle} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-8 px-6 pt-6 pb-20 lg:mx-0 lg:ml-[8vw]">
                <h1 className="text-2xl font-medium tracking-[-0.01em]">
                    {commitmentCopy.listTitle}
                </h1>

                {open.length === 0 ? (
                    <p className="text-muted-foreground">
                        {commitmentCopy.listEmpty}
                    </p>
                ) : (
                    <ul className="divide-border border-border divide-y border-t">
                        {open.map((commitment) => (
                            <li key={commitment.id} className="py-5">
                                <CommitmentRow
                                    id={commitment.id}
                                    description={commitment.description}
                                    provenance={commitment.provenance}
                                    awaitingConfirmation={
                                        commitment.awaitingConfirmation
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}

                <Link
                    href={home()}
                    className="text-muted-foreground hover:text-foreground font-mono text-[11px] tracking-[0.16em] uppercase transition-colors"
                >
                    Back to home
                </Link>
            </div>
        </>
    );
}
