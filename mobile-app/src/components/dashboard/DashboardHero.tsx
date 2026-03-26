import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Dimensions } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '@contexts/ThemeContext';
import { Avatar } from '@components/common/Avatar';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { Palette } from '@styles/palette';
import Icon from 'react-native-vector-icons/MaterialIcons';

const { width: SCREEN_W } = Dimensions.get('window');

interface DashboardHeroProps {
    greeting: string;
    userName: string;
    roleLabel: string;
    avatarUrl?: string;
    showSettings?: boolean;
    onPressNotifications?: () => void;
    onPressSettings?: () => void;
}

export const DashboardHero: React.FC<DashboardHeroProps> = ({
    greeting,
    userName,
    roleLabel,
    avatarUrl,
    showSettings = true,
    onPressNotifications,
    onPressSettings,
}) => {
    const { colors } = useTheme();
    const insets = useSafeAreaInsets();

    const gradient: [string, string, string] = [
        colors.primaryDark ?? colors.primary,
        colors.primary,
        colors.primaryLight ?? colors.primary,
    ];

    return (
        <LinearGradient colors={gradient} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.gradient}>
            <View style={[styles.inner, { paddingTop: Math.max(insets.top, SPACING.md) + SPACING.sm }]}>
                <View style={styles.topRow}>
                    <View style={styles.identity}>
                        <Avatar name={userName} imageUrl={avatarUrl} size={52} />
                        <View style={styles.textCol}>
                            <Text style={styles.greet}>{greeting}</Text>
                            <Text style={styles.name} numberOfLines={1}>
                                {userName}
                            </Text>
                            <View style={styles.pill}>
                                <Text style={styles.pillText}>{roleLabel}</Text>
                            </View>
                        </View>
                    </View>
                    <View style={styles.actions}>
                        {onPressNotifications ? (
                            <TouchableOpacity onPress={onPressNotifications} style={styles.iconBtn} hitSlop={12}>
                                <Icon name="notifications-none" size={26} color={Palette.onPrimary} />
                            </TouchableOpacity>
                        ) : null}
                        {showSettings && onPressSettings ? (
                            <TouchableOpacity onPress={onPressSettings} style={styles.iconBtn} hitSlop={12}>
                                <Icon name="settings" size={24} color={Palette.onPrimary} />
                            </TouchableOpacity>
                        ) : null}
                    </View>
                </View>
                <View style={[styles.decor, { width: SCREEN_W * 0.35 }]} />
            </View>
        </LinearGradient>
    );
};

const styles = StyleSheet.create({
    gradient: {
        borderBottomLeftRadius: 24,
        borderBottomRightRadius: 24,
        overflow: 'hidden',
    },
    inner: {
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.xl,
    },
    topRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
    },
    identity: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        flex: 1,
        minWidth: 0,
    },
    textCol: {
        flex: 1,
        minWidth: 0,
    },
    greet: {
        fontSize: FONT_SIZES.sm,
        color: 'rgba(255,255,255,0.85)',
        fontWeight: '500',
    },
    name: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: '800',
        color: Palette.onPrimary,
        marginTop: 2,
        letterSpacing: -0.3,
    },
    pill: {
        alignSelf: 'flex-start',
        marginTop: SPACING.sm,
        paddingHorizontal: SPACING.sm,
        paddingVertical: 4,
        borderRadius: 999,
        backgroundColor: 'rgba(255,255,255,0.22)',
    },
    pillText: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '700',
        color: Palette.onPrimary,
        letterSpacing: 0.3,
    },
    actions: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.xs,
    },
    iconBtn: {
        padding: SPACING.xs,
    },
    decor: {
        position: 'absolute',
        right: -20,
        bottom: -40,
        height: 100,
        borderRadius: 50,
        backgroundColor: 'rgba(255,255,255,0.08)',
    },
});
