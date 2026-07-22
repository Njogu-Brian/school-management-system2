import type { BottomTabNavigationOptions } from '@react-navigation/bottom-tabs';
import type { EdgeInsets } from 'react-native-safe-area-context';

type ThemeColors = {
  primary: string;
  palette: {
    surface: string;
    textMuted: string;
    border: string;
  };
  isDark: boolean;
};

export function getDefaultTabScreenOptions(
  insets: EdgeInsets,
  theme: ThemeColors,
): BottomTabNavigationOptions {
  return {
    headerShown: false,
    tabBarActiveTintColor: theme.primary,
    tabBarInactiveTintColor: theme.palette.textMuted,
    tabBarStyle: {
      backgroundColor: theme.palette.surface,
      borderTopColor: theme.palette.border,
      paddingBottom: Math.max(insets.bottom, 6),
      height: 56 + Math.max(insets.bottom, 6),
    },
    tabBarLabelStyle: {
      fontSize: 11,
      fontWeight: '600',
    },
  };
}
