import type { CommitmentProvenance } from '@add/shared';
import { commitmentProvenanceLabels, commitmentResponses } from '@add/shared';
import { Responses } from '@/components/responses';
import commitments from '@/routes/commitments';

/** One commitment and its answers, the same on home's band and on the full list. */
export function CommitmentRow({
    id,
    description,
    provenance,
    awaitingConfirmation,
}: {
    id: string;
    description: string;
    provenance: CommitmentProvenance | null;
    awaitingConfirmation: boolean;
}) {
    return (
        <div className="space-y-2">
            <p>
                {description}
                {provenance && (
                    <span className="text-muted-foreground font-mono text-[13px]">
                        {' '}
                        · {commitmentProvenanceLabels[provenance]}
                    </span>
                )}
            </p>
            <Responses
                action={commitments.respond.form(id)}
                responses={commitmentResponses(awaitingConfirmation)}
            />
        </div>
    );
}
