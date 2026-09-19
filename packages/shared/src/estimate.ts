/** Both frontends say the same thing about how long a step takes. */
export function formatEstimate(seconds: number | null): string | null {
    if (seconds === null || seconds <= 0) {
        return null;
    }

    if (seconds < 90) {
        return `${Math.round(seconds / 10) * 10} seconds`;
    }

    const minutes = Math.round(seconds / 60);

    if (minutes < 60) {
        return `${minutes} minutes`;
    }

    const hours = Math.round(minutes / 30) / 2;

    return hours === 1 ? '1 hour' : `${hours} hours`;
}
