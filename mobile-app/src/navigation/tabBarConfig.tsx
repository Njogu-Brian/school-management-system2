import React from 'react';
import { Platform, StyleSheet, View } from 'react-native';
import type { BottomTabNavigationOptions } from '@react-navigation/bottom-tabs';
import type { EdgeInsets } from 'react-native-safe-area-context';

/** Visible tab bar content (icons + labels), excluding system inset. */
export const TAB_BAR_CONTENT_HEIGHT = 56;

type TabBarColors = {
    surfaceDark: string;
    surfaceLight: string;
    borderDark: string;
    borderLight: string;
    textSubDark: string;
    textSubLight: string;
};

export function getTabBarStyle(
    insets: EdgeInsets,
    colors: TabBarColors,
    isDark: boolean
): NonNullable<BottomTabNavigationOptions['tabBarStyle']> {
    const backgroundColor = isDark ? colors.surfaceDark : colors.surfaceLight;
    const borderTopColor = isDark ? colors.borderDark : colors.borderLight;
    const bottomInset = insets.bottom;

    return {
        backgroundColor,
        borderTopColor,
        borderTopWidth: StyleSheet.hairlineWidth,
        height: TAB_BAR_CONTENT_HEIGHT + bottomInset,
        paddingTop: 6,
        paddingBottom: bottomInset,
        marginBottom: 0,
        ...(Platform.OS === 'android' ? { elevation: 0 } : {}),
    };
}

export function getTabBarBackground(colors: TabBarColors, isDark: boolean) {
    const backgroundColor = isDark ? colors.surfaceDark : colors.surfaceLight;
    return () => <View style={{ flex: 1, backgroundColor }} />;
}

export function getDefaultTabScreenOptions(
    insets: EdgeInsets,
    colors: TabBarColors,
    isDark: boolean,
    activeTint: string
): BottomTabNavigationOptions {
    return {
        headerShown: false,
        tabBarActiveTintColor: activeTint,
        tabBarInactiveTintColor: isDark ? colors.textSubDark : colors.textSubLight,
        tabBarStyle: getTabBarStyle(insets, colors, isDark),
        tabBarBackground: getTabBarBackground(colors, isDark),
        tabBarItemStyle: { paddingVertical: 0 },
        tabBarHideOnKeyboard: true,
        sceneContainerStyle: { paddingBottom: 0 },
    };
}
