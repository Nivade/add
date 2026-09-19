/** A band is a hairline, a mono label and its answer. No boxes: a box implies a list to work through. */
export function Band({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    const id = `band-${label.replace(/\s+/g, '-').toLowerCase()}`;

    return (
        <section aria-labelledby={id} className="border-border border-t pt-5">
            <h2
                id={id}
                className="text-muted-foreground mb-4 font-mono text-[11px] tracking-[0.18em] uppercase"
            >
                {label}
            </h2>
            {children}
        </section>
    );
}
