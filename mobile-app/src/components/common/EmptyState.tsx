import React from 'react';
import {
    View,
    Text,
    StyleSheet,
    ActivityIndicator,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface EmptyStateProps {
    icon: string;
    title: string;
    message: string;
    action?: React.ReactNode;
}

export const EmptyState: React.FC<EmptyStateProps> = ({ icon, title, message, action }) => {
    const { isDark, colors } = useTheme();

    return (
        <View style={styles.container}>
            <View
                style={[
                    styles.iconContainer,
                    { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight },
                ]}
            >
                <Icon name={icon} size={48} color={isDark ? colors.textSubDark : colors.textSubLight} />
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
