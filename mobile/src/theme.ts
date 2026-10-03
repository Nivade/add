import {
  AtkinsonHyperlegibleMono_400Regular,
  AtkinsonHyperlegibleMono_500Medium,
} from '@expo-google-fonts/atkinson-hyperlegible-mono';
import {
  AtkinsonHyperlegibleNext_400Regular,
  AtkinsonHyperlegibleNext_600SemiBold,
  AtkinsonHyperlegibleNext_700Bold,
} from '@expo-google-fonts/atkinson-hyperlegible-next';
import {
  type ColorToken,
  darkColors,
  lightColors,
  radius,
  typeScale,
} from '@add/shared';
import { useMemo } from 'react';
import {
  StyleSheet,
  useColorScheme,
  type TextStyle,
  type ViewStyle,
} from 'react-native';

/** One-handed reach: nothing interactive is smaller than this. */
export const TOUCH_TARGET = 56;

export const fonts = {
  AtkinsonHyperlegibleNext_400Regular,
  AtkinsonHyperlegibleNext_600SemiBold,
  AtkinsonHyperlegibleNext_700Bold,
  AtkinsonHyperlegibleMono_400Regular,
  AtkinsonHyperlegibleMono_500Medium,
};

const textFamily = {
  400: 'AtkinsonHyperlegibleNext_400Regular',
  600: 'AtkinsonHyperlegibleNext_600SemiBold',
  700: 'AtkinsonHyperlegibleNext_700Bold',
} as const;

const monoFamily = {
  400: 'AtkinsonHyperlegibleMono_400Regular',
  500: 'AtkinsonHyperlegibleMono_500Medium',
} as const;

type Colors = { [Token in ColorToken]: string };

type Role = { size: number; lineHeight: number; weight: number; tracking?: number };

/** React Native picks a weight by family, so no role sets `fontWeight`. */
function textStyle(role: Role, family: Record<number, string>): TextStyle {
  return {
    fontFamily: family[role.weight],
    fontSize: role.size,
    lineHeight: Math.round(role.size * role.lineHeight),
    ...(role.tracking ? { letterSpacing: role.tracking * role.size } : {}),
  };
}

function buildTheme(colors: Colors) {
  const type = {
    oneThing: textStyle(typeScale.oneThing, textFamily),
    lead: textStyle(typeScale.lead, textFamily),
    body: textStyle(typeScale.body, textFamily),
    bandHeading: textStyle(typeScale.bandHeading, textFamily),
    small: textStyle(typeScale.small, textFamily),
    numeric: {
      ...textStyle(typeScale.numeric, monoFamily),
      fontVariant: ['tabular-nums'],
    } satisfies TextStyle,
    controlLabel: textStyle({ ...typeScale.body, weight: 600 }, textFamily),
    actionLabel: textStyle({ ...typeScale.lead, weight: 600 }, textFamily),
  };

  const space = (steps: number): number => steps * 8;

  const field: TextStyle & ViewStyle = {
    ...type.body,
    minHeight: TOUCH_TARGET,
    paddingHorizontal: space(2),
    borderRadius: radius.field,
    borderWidth: 1,
    borderColor: colors.field,
    backgroundColor: colors.surface,
    color: colors.ink,
  };

  return { colors, type, typeScale, radius, space, field };
}

export type Theme = ReturnType<typeof buildTheme>;

const themes = {
  light: buildTheme(lightColors),
  dark: buildTheme(darkColors),
};

/** The phone's own scheme decides; there is no in-app switch. */
export function useTheme(): Theme {
  return useColorScheme() === 'dark' ? themes.dark : themes.light;
}

/** A hook that memoises the sheet per scheme, so a screen's styles are written once against the tokens. */
export function makeStyles<T extends StyleSheet.NamedStyles<T> | StyleSheet.NamedStyles<any>>(
  build: (theme: Theme) => T,
): () => T {
  return function useStyles() {
    const theme = useTheme();

    return useMemo(() => StyleSheet.create(build(theme)), [theme]);
  };
}
