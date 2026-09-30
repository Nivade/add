export const lightColors = {
    paper: '#F2F4F5',
    surface: '#FFFFFF',
    ink: '#172330',
    muted: '#4F5D6B',
    line: '#D7DDE1',
    field: '#6F808C',
    now: '#0A6B80',
    onNow: '#FFFFFF',
} as const;

export const darkColors = {
    paper: '#0F1B22',
    surface: '#162530',
    ink: '#E3EBEE',
    muted: '#9AADB5',
    line: '#27363F',
    field: '#6F8793',
    now: '#5BC3D9',
    onNow: '#0F1B22',
} as const;

export type ColorToken = keyof typeof lightColors;

export type DayStripStop = { name: string; minute: number; color: string };

export const dayStripLight: readonly DayStripStop[] = [
    { name: 'dawn', minute: 6 * 60, color: '#F4D8C8' },
    { name: 'morning', minute: 9 * 60, color: '#F6EBC8' },
    { name: 'noon', minute: 13 * 60, color: '#F4F2E6' },
    { name: 'afternoon', minute: 17 * 60, color: '#EADFC2' },
    { name: 'dusk', minute: 20 * 60, color: '#C9C1E6' },
    { name: 'night', minute: 23 * 60, color: '#2E3A63' },
];

export const dayStripDark: readonly DayStripStop[] = [
    { name: 'dawn', minute: 6 * 60, color: '#3B2B31' },
    { name: 'morning', minute: 9 * 60, color: '#3A3527' },
    { name: 'noon', minute: 13 * 60, color: '#25313A' },
    { name: 'afternoon', minute: 17 * 60, color: '#3A3326' },
    { name: 'dusk', minute: 20 * 60, color: '#2E2946' },
    { name: 'night', minute: 23 * 60, color: '#121A2E' },
];

/** Pixels, for React Native; the web reads the same values from app.css. */
export const radius = { control: 12, field: 8, panel: 16 } as const;

/** Pixels at a 16px root; `oneThing` grows from `size` to `maxSize` with the viewport on the web. */
export const typeScale = {
    oneThing: { size: 36, maxSize: 60, lineHeight: 1.1, weight: 600, tracking: -0.015 },
    lead: { size: 18, lineHeight: 1.55, weight: 400 },
    body: { size: 16, lineHeight: 1.55, weight: 400 },
    bandHeading: { size: 16, lineHeight: 1.4, weight: 600 },
    small: { size: 14, lineHeight: 1.4, weight: 400 },
    numeric: { size: 15, lineHeight: 1.4, weight: 500 },
} as const;
