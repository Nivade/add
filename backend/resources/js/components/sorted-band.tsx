import type { CaptureKind, SortedCaptureData } from '@add/shared';
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
import { Button } from '@/components/ui/button';
import captures from '@/routes/captures';

function ChangeKind({
    id,
    kind,
    label,
}: {
    id: string;
    kind: CaptureKind;
    label: string;
}) {
    return (
        <Form
            {...captures.kind.form(id)}
            transform={(data) => ({ ...data, kind })}
            options={{ preserveScroll: true }}
        >
            <Button type="submit" variant="quiet">
                {label}
            </Button>
        </Form>
    );
}

function Confirm({ id }: { id: string }) {
    return (
        <Form {...captures.confirm.form(id)} options={{ preserveScroll: true }}>
            <Button type="submit" variant="quiet">
                {sortedCopy.right}
            </Button>
        </Form>
    );
}

function SortedItem({ item }: { item: SortedCaptureData }) {
    const [choosing, setChoosing] = useState(false);
    const choicesId = useId();

    return (
        <li className="space-y-2">
            <p>“{item.excerpt}”</p>
            <p className="text-muted-foreground">
                {sortedLine(item.kind, item.detail)}
            </p>
            <div className="flex flex-wrap gap-x-6">
                {item.kind === 'not_for_you' && (
                    <ChangeKind
                        id={item.id}
                        kind="thought"
                        label={sortedCopy.keep}
                    />
                )}
                <Confirm id={item.id} />
                {item.kind !== 'not_for_you' && (
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
                <div id={choicesId} className="flex flex-wrap gap-x-6">
                    {captureKindChoices
                        .filter(({ value }) => value !== item.kind)
                        .map(({ value, label }) => (
                            <ChangeKind
                                key={value}
                                id={item.id}
                                kind={value}
                                label={label}
                            />
                        ))}
                </div>
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
