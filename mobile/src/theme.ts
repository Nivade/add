/** One-handed reach: nothing interactive is smaller than this. */
export const TOUCH_TARGET = 56;

export const theme = {
  color: {
    background: '#0B0B0F',
    surface: '#15151C',
    border: '#2A2A36',
    text: '#F2F2F5',
    muted: '#9A9AA8',
    now: '#8FD6FF',
  },
  space: (steps: number): number => steps * 8,
  radius: 14,
  label: {
    fontSize: 11,
    letterSpacing: 2,
    textTransform: 'uppercase',
  },
} as const;
