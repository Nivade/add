import { checkInCopy, checkInQuestions, checkInResponses } from '@add/shared';
import type { CheckInTopic } from '@add/shared';
import { Band } from '@/components/band';
import { Responses } from '@/components/responses';
import checkIns from '@/routes/check-ins';

/** The quietest band on home: it never competes with Start, and its absence after an answer is the whole confirmation. */
export function CheckIn({ topic }: { topic: CheckInTopic }) {
    return (
        <Band label={checkInCopy.band}>
            <p>{checkInQuestions[topic]}</p>
            <p className="text-muted-foreground mt-2">{checkInCopy.meta}</p>
            <div className="mt-3">
                <Responses
                    action={checkIns.store.form(topic)}
                    responses={checkInResponses}
                />
            </div>
        </Band>
    );
}
