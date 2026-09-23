import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy, update } from '@/routes/calendar';

export default function Calendar({
    connectedHost,
}: {
    connectedHost: string | null;
}) {
    return (
        <>
            <Head title="Calendar settings" />

            <h1 className="sr-only">Calendar settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Calendar"
                    description="Read your day from your calendar's private address. Nothing is ever written back to it."
                />

                {connectedHost !== null && (
                    <Form
                        {...destroy.form()}
                        options={{ preserveScroll: true }}
                        className="flex items-center gap-4"
                    >
                        {({ processing }) => (
                            <>
                                <p className="text-sm">
                                    Reading from {connectedHost}.
                                </p>

                                <Button
                                    variant="secondary"
                                    disabled={processing}
                                    data-test="disconnect-calendar-button"
                                >
                                    Disconnect
                                </Button>
                            </>
                        )}
                    </Form>
                )}

                <Form
                    {...update.form()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="url">
                                    {connectedHost === null
                                        ? 'Private calendar address'
                                        : 'Replace the address'}
                                </Label>

                                <Input
                                    id="url"
                                    type="url"
                                    className="mt-1 block w-full"
                                    name="url"
                                    required
                                    autoComplete="off"
                                    placeholder="https://… or webcal://…"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.url}
                                />
                            </div>

                            <Button
                                disabled={processing}
                                data-test="connect-calendar-button"
                            >
                                Connect
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
