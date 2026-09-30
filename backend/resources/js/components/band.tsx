import { useId } from 'react';

/** A band is a question and its answer, set apart by space. No boxes: a box implies a list to work through. */
export function Band({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    const id = useId();

    return (
        <section aria-labelledby={id}>
            <h2
                id={id}
                className="text-muted-foreground text-body mb-3 leading-snug font-semibold"
            >
                {label}
            </h2>
            {children}
        </section>
    );
}
