import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ViewStyle } from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';

type Props = {
    message: string;
    onRetry?: () => void;
    retryLabel?: string;
    style?: ViewStyle;
    /** Theme */
    surfaceColor: string;
    borderColor: string;
    textColor: string;
    subColor: string;
    accentColor: string;
};

/**
 * Non-blocking load / API failure banner (dashboards, finance, etc.).
 */
export const LoadErrorBanner: React.FC<Props> = ({
    message,
    onRetry,
    retryLabel = 'Retry',
    style,
    surfaceColor,
    borderColor,
    textColor,
    subColor,
    accentColor,
}) => {
    return (
        <View
            style={[
                styles.wrap,
                {
                    backgroundColor: surfaceColor,
                    borderColor,
                },
                style,
            ]}
        >
            <Icon name="error-outline" size={22} color={accentColor} style={styles.icon} />
            <View style={styles.body}>
                <Text style={[styles.title, { color: textColor }]}>Something went wrong</Text>
                <Text style={[styles.msg, { color: subColor }]}>{message}</Text>
                {onRetry ? (
                    <TouchableOpacity onPress={onRetry} hitSlop={8} style={styles.retryBtn}>
                        <Text style={[styles.retryText, { color: accentColor }]}>{retryLabel}</Text>
                    </TouchableOpacity>
                ) : null}
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    wrap: {
        flexDirection: 'row',
        alignItems: 'flex-start',
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
        marginBottom: SPACING.md,
    },
    icon: { marginRight: SPACING.sm, marginTop: 2 },
    body: { flex: 1 },
    title: { fontSize: FONT_SIZES.sm, fontWeight: '700', marginBottom: 4 },
    msg: { fontSize: FONT_SIZES.sm, lineHeight: 20 },
    retryBtn: { marginTop: SPACING.sm, alignSelf: 'flex-start' },
    retryText: { fontSize: FONT_SIZES.sm, fontWeight: '700' },
});
