import type { CommitmentListData } from '@add/shared';
import { commitmentProvenanceLabels, commitmentResponses } from '@add/shared';
import { Head, Link } from '@inertiajs/react';
import { Responses } from '@/components/responses';
import { home } from '@/routes';
import commitments from '@/routes/commitments';

/** Reached only from home's one commitment; home stays the place things are chosen from. */
export default function Commitments({
    list: { commitments: open },
}: {
    list: CommitmentListData;
}) {
    return (
        <>
            <Head title="What you said you'd do" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-8 px-6 pt-6 pb-20 lg:mx-0 lg:ml-[8vw]">
                <h1 className="text-2xl font-medium tracking-[-0.01em]">
                    What you said you'd do
                </h1>

                {open.length === 0 ? (
                    <p className="text-muted-foreground">
                        Nothing is open. Anything you say you'll do lands here.
                    </p>
                ) : (
                    <ul className="divide-border border-border divide-y border-t">
                        {open.map((commitment) => {
                            return (
                                <li
                                    key={commitment.id}
                                    className="space-y-2 py-5"
                                >
                                    <p>{commitment.description}</p>
                                    <p className="text-muted-foreground font-mono text-[13px]">
                                        {
                                            commitmentProvenanceLabels[
                                                commitment.provenance
                                            ]
                                        }
                                    </p>
                                    <Responses
                                        action={commitments.respond.form(
                                            commitment.id,
                                        )}
                                        responses={commitmentResponses(
                                            commitment.awaitingConfirmation,
                                        )}
                                    />
                                </li>
                            );
                        })}
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
