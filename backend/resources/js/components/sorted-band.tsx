import type { SortedCaptureData } from '@add/shared';
import {
    captureKindChoices,
    homeBands,
    sortedCopy,
    sortedLine,
    sortedMoreLine,
} from '@add/shared';
import { Form } from '@inertiajs/react';
import { useId, useState } from 'react';
import { Band } from '@/components/band';
import { Responses } from '@/components/responses';
import { Button } from '@/components/ui/button';
import { useSingleFlight } from '@/hooks/use-single-flight';
import captures from '@/routes/captures';

function Confirm({ id }: { id: string }) {
    const singleFlight = useSingleFlight();

    return (
        <Form
            {...captures.confirm.form(id)}
            {...singleFlight}
            options={{ preserveScroll: true }}
        >
            <Button type="submit" variant="quiet">
                {sortedCopy.right}
            </Button>
        </Form>
    );
}

function SortedItem({ item }: { item: SortedCaptureData }) {
    const [choosing, setChoosing] = useState(false);
    const choicesId = useId();
    const notForYou = item.kind === 'not_for_you';

    return (
        <li className="space-y-2">
            <p>“{item.excerpt}”</p>
            <p className="text-muted-foreground">
                {sortedLine(item.kind, item.detail)}
            </p>
            <div className="flex flex-wrap gap-x-6">
                {notForYou && (
                    <Responses
                        action={captures.kind.form(item.id)}
                        name="kind"
                        responses={[
                            { value: 'thought', label: sortedCopy.keep },
                        ]}
                    />
                )}
                <Confirm id={item.id} />
                {!notForYou && (
                    <Button
                        type="button"
                        variant="quiet"
                        aria-expanded={choosing}
                        aria-controls={choicesId}
                        onClick={() => setChoosing(!choosing)}
                    >
                        {sortedCopy.notRight}
                    </Button>
                )}
            </div>
            {choosing && (
                <Responses
                    id={choicesId}
                    action={captures.kind.form(item.id)}
                    name="kind"
                    responses={captureKindChoices.filter(
                        ({ value }) => value !== item.kind,
                    )}
                />
            )}
        </li>
    );
}

/** Everything the app sorted for the person is read back here until they say it is right, or change it. */
export function SortedBand({
    sorted,
    more,
}: {
    sorted: SortedCaptureData[];
    more: number;
}) {
    if (sorted.length === 0) {
        return null;
    }

    return (
        <Band label={homeBands.sorted}>
            <ul className="space-y-6">
                {sorted.map((item) => (
                    <SortedItem key={item.id} item={item} />
                ))}
            </ul>
            {more > 0 && (
                <p className="text-muted-foreground mt-4">
                    {sortedMoreLine(more)}
                </p>
            )}
        </Band>
    );
}
