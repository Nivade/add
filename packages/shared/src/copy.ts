/** The count may be stated, never enumerated, so both frontends state it the same way. */
export function restCountLine(count: number): string {
    if (count === 0) {
        return 'nothing else is waiting';
    }

    return `${count} other ${count === 1 ? 'thing' : 'things'}, none of which you need to think about`;
}
