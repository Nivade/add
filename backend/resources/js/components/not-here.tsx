import { notHereLabels } from '@add/shared';
import type { Place } from '@add/shared';
import { Form } from '@inertiajs/react';
import { quietLineClassName } from '@/components/responses';
import whereabouts from '@/routes/whereabouts';

/** Answers the guess the why just stated; the page re-ranks, so no confirmation follows. */
export function NotHere({ place }: { place: Place }) {
    return (
        <Form
            {...whereabouts.notHere.form()}
            options={{ preserveScroll: true }}
        >
            <input type="hidden" name="place" value={place} />
            <button type="submit" className={quietLineClassName}>
                {notHereLabels[place]}
            </button>
        </Form>
    );
}
