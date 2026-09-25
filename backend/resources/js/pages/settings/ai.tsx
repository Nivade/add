import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { update } from '@/routes/ai';

export default function Ai({ consented }: { consented: boolean }) {
    return (
        <>
            <Head title="AI settings" />

            <h1 className="sr-only">AI settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="AI"
                    description="Turning this on lets a capture be decomposed by a model outside this server. Off by default, and nothing you write leaves this server until you turn it on."
                />

                <Form
                    {...update.form()}
                    transform={(data) => ({
                        ...data,
                        consented: !consented,
                    })}
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <>
                            <p className="text-sm">
                                {consented
                                    ? 'A model outside this server can read what you capture.'
                                    : 'Nothing you write leaves this server.'}
                            </p>

                            <Button
                                variant={consented ? 'secondary' : 'default'}
                                disabled={processing}
                                data-test="ai-consent-button"
                            >
                                {consented ? 'Turn off' : 'Turn on'}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
