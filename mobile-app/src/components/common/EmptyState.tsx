import React, { useMemo } from 'react';
import { View, Text, StyleSheet, ActivityIndicator } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

export type EmptyStateAccent = 'primary' | 'gold' | 'info' | 'neutral';

interface EmptyStateProps {
    icon: string;
    title: string;
    message: string;
    action?: React.ReactNode;
    /** Sovereign Modernism–style icon treatment (dynamic primary still applies). */
    accent?: EmptyStateAccent;
}

export const EmptyState: React.FC<EmptyStateProps> = ({ icon, title, message, action, accent = 'neutral' }) => {
    const { isDark, colors } = useTheme();

    const { circleBg, iconColor } = useMemo(() => {
        switch (accent) {
            case 'primary':
                return {
                    circleBg: isDark ? `${colors.primary}30` : colors.accentLight,
                    iconColor: colors.primary,
                };
            case 'gold':
                return {
                    circleBg: isDark ? `${colors.warning}35` : `${colors.warning}22`,
                    iconColor: colors.warning,
                };
            case 'info':
                return {
                    circleBg: isDark ? `${colors.info}30` : `${colors.info}18`,
                    iconColor: colors.info,
                };
            default:
                return {
                    circleBg: isDark ? colors.surfaceDark : colors.surfaceLight,
                    iconColor: isDark ? colors.textSubDark : colors.textSubLight,
                };
        }
    }, [accent, isDark, colors]);

    return (
        <View style={styles.container}>
            <View style={[styles.iconContainer, { backgroundColor: circleBg }]}>
                <Icon name={icon} size={48} color={iconColor} />
            </View>
            <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                {title}
            </Text>
            <Text style={[styles.message, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                {message}
            </Text>
            {action && <View style={styles.action}>{action}</View>}
        </View>
    );
};

interface LoadingStateProps {
    message?: string;
}

export const LoadingState: React.FC<LoadingStateProps> = ({ message = 'Loading...' }) => {
    const { isDark, colors } = useTheme();

    return (
        <View style={styles.container}>
            <ActivityIndicator size="large" color={colors.primary} />
            <Text
                style={[styles.loadingText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}
            >
                {message}
            </Text>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        padding: SPACING.xl,
    },
    iconContainer: {
        width: 96,
        height: 96,
        borderRadius: 48,
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: SPACING.lg,
    },
    title: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
        marginBottom: SPACING.sm,
        textAlign: 'center',
    },
    message: {
        fontSize: FONT_SIZES.sm,
        textAlign: 'center',
        marginBottom: SPACING.lg,
    },
    action: {
        marginTop: SPACING.md,
    },
    loadingText: {
        fontSize: FONT_SIZES.sm,
        marginTop: SPACING.md,
    },
});
