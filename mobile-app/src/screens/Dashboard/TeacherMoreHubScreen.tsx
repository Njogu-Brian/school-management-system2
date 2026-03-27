import React from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { isSeniorTeacherRole } from '@utils/roleUtils';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Item {
    title: string;
    icon: string;
    screen: string;
    seniorOnly?: boolean;
}

interface Props {
    navigation: { navigate: (name: string, params?: object) => void };
}

export const TeacherMoreHubScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user, logout } = useAuth();
    const isSenior = user?.role ? isSeniorTeacherRole(user.role) : false;

    const items: Item[] = [
        { title: 'My profile', icon: 'person', screen: 'MyProfile' },
        ...(user?.staff_id ? [{ title: 'Edit profile', icon: 'edit', screen: 'StaffEdit' } as Item] : []),
        { title: 'My salary / payslips', icon: 'payments', screen: 'MySalary' },
        { title: 'Leave & apply', icon: 'event-busy', screen: 'Leave' },
        { title: 'Clock in / out', icon: 'access-time', screen: 'TeacherClock' },
        { title: 'Transport routes', icon: 'directions-bus', screen: 'Transport' },
        { title: 'Diary', icon: 'book', screen: 'Diary' },
        { title: 'Notifications', icon: 'notifications', screen: 'Notifications' },
        { title: 'Settings', icon: 'settings', screen: 'Settings' },
        { title: 'Supervised classes', icon: 'groups', screen: 'SupervisedClassrooms', seniorOnly: true },
        { title: 'Supervised staff', icon: 'badge', screen: 'SupervisedStaff', seniorOnly: true },
        { title: 'Fee balances', icon: 'account-balance-wallet', screen: 'FeeBalances', seniorOnly: true },
        { title: 'Logout', icon: 'logout', screen: '__logout__' },
    ];

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : colors.textMainLight;
    const textSub = isDark ? colors.textSubDark : colors.textSubLight;
    const surface = isDark ? colors.surfaceDark : BRAND.surface;

    return (
        <SafeAreaView style={[styles.root, { backgroundColor: bg }]}>
            <ScrollView contentContainerStyle={styles.list} showsVerticalScrollIndicator={false}>
                {items
                    .filter((i) => !i.seniorOnly || isSenior)
                    .map((i) => (
                        <TouchableOpacity
                            key={i.screen}
                            style={[
                                styles.row,
                                {
                                    backgroundColor: surface,
                                    borderColor: isDark ? colors.borderDark : BRAND.border,
                                },
                            ]}
                            onPress={() =>
                                i.screen === '__logout__'
                                    ? logout()
                                    : navigation.navigate(
                                          i.screen,
                                          i.screen === 'StaffEdit' && user?.staff_id
                                              ? { staffId: user.staff_id }
                                              : undefined
                                      )
                            }
                            activeOpacity={0.7}
                        >
                            <View style={[styles.iconWrap, { backgroundColor: colors.primary + '18' }]}>
                                <Icon name={i.icon} size={22} color={colors.primary} />
                            </View>
                            <Text style={[styles.rowTitle, { color: textMain }]}>{i.title}</Text>
                            <Icon name="chevron-right" size={22} color={textSub} />
                        </TouchableOpacity>
                    ))}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    root: { flex: 1 },
    list: { padding: SPACING.lg, paddingTop: SPACING.md, paddingBottom: SPACING.xxl, gap: SPACING.sm },
    row: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: SPACING.md,
        borderRadius: RADIUS.card,
        borderWidth: 1,
        marginBottom: SPACING.sm,
    },
    iconWrap: {
        width: 40,
        height: 40,
        borderRadius: 12,
        alignItems: 'center',
        justifyContent: 'center',
        marginRight: SPACING.md,
    },
    rowTitle: { flex: 1, fontSize: FONT_SIZES.md, fontWeight: '600' },
});
