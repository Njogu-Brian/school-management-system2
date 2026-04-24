import React from 'react';
import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { useTheme } from '@contexts/ThemeContext';

interface AppScreenHeaderProps {
    title: string;
    subtitle?: string;
    onBack?: () => void;
    rightIcon?: string;
    onRightPress?: () => void;
    showBack?: boolean;
}

// Uniform, theme-aware header used on every non-dashboard screen. Gradient and colors
// come from ThemeContext so brand switching takes effect automatically.
export const AppScreenHeader: React.FC<AppScreenHeaderProps> = ({
    title,
    subtitle,
    onBack,
    rightIcon,
    onRightPress,
    showBack = true,
}) => {
    const { colors } = useTheme();

    const gradient: [string, string] = [
        colors.primaryDark ?? colors.primary,
        colors.primary,
    ];

    return (
        <SafeAreaView edges={['top']} style={[styles.safeArea, { backgroundColor: gradient[0] }]}>
            <LinearGradient colors={gradient} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }}>
                <View style={styles.row}>
                    {showBack && onBack ? (
                        <TouchableOpacity onPress={onBack} style={styles.iconButton} hitSlop={10}>
                            <Icon name="arrow-back" size={24} color="#fff" />
                        </TouchableOpacity>
                    ) : (
                        <View style={styles.iconSpacer} />
                    )}
                    <View style={styles.titleWrap}>
                        <Text style={styles.title} numberOfLines={1}>
                            {title}
                        </Text>
                        {subtitle ? (
                            <Text style={styles.subtitle} numberOfLines={1}>
                                {subtitle}
                            </Text>
                        ) : null}
                    </View>
                    {rightIcon && onRightPress ? (
                        <TouchableOpacity onPress={onRightPress} style={styles.iconButton} hitSlop={10}>
                            <Icon name={rightIcon} size={22} color="#fff" />
                        </TouchableOpacity>
                    ) : (
                        <View style={styles.iconSpacer} />
                    )}
                </View>
            </LinearGradient>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    safeArea: {
        // background color is overridden inline with theme
    },
    row: {
        minHeight: 54,
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: SPACING.lg,
        paddingVertical: SPACING.sm,
    },
    iconButton: {
        width: 36,
        height: 36,
        alignItems: 'center',
        justifyContent: 'center',
    },
    iconSpacer: {
        width: 36,
        height: 36,
    },
    titleWrap: {
        flex: 1,
        marginHorizontal: SPACING.sm,
    },
    title: {
        color: '#fff',
        fontSize: FONT_SIZES.lg,
        fontWeight: '700',
    },
    subtitle: {
        color: 'rgba(255,255,255,0.85)',
        fontSize: FONT_SIZES.xs,
        marginTop: 2,
    },
});
