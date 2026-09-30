import type { CommitmentListData } from '@add/shared';
import { commitmentCopy } from '@add/shared';
import { Head, Link } from '@inertiajs/react';
import { CommitmentRow } from '@/components/commitment-row';
import { quietLineClassName } from '@/components/responses';
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

            <div className="flex flex-col items-start gap-10">
                <h1 className="text-one-thing text-balance">
                    {commitmentCopy.listTitle}
                </h1>

                {open.length === 0 ? (
                    <p className="text-muted-foreground">
                        {commitmentCopy.listEmpty}
                    </p>
                ) : (
                    <ul className="w-full space-y-8">
                        {open.map((commitment) => (
                            <li key={commitment.id}>
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

                <Link href={home()} className={quietLineClassName}>
                    Back to home
                </Link>
            </div>
        </>
    );
}
