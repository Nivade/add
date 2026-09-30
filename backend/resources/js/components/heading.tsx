export default function Heading({
    title,
    description,
    variant = 'default',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-0.5'}>
            <h2
                className={
                    variant === 'small'
                        ? 'text-body mb-0.5 font-medium'
                        : 'text-lead font-semibold'
                }
            >
                {title}
            </h2>
            {description && (
                <p className="text-muted-foreground text-small">
                    {description}
                </p>
            )}
        </header>
    );
}
