import React from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { navigate: (name: string, params?: object) => void };
}

export const StudentHomeScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: bg }]}>
            <ScrollView contentContainerStyle={styles.scroll}>
                <Text style={[styles.greet, { color: textSub }]}>Hello</Text>
                <Text style={[styles.name, { color: textMain }]}>{user?.name || 'Student'}</Text>
                <Card style={{ borderRadius: RADIUS.card, padding: SPACING.lg }}>
                    <Text style={[styles.body, { color: textSub }]}>
                        Homework, results, and timetables will appear here as your school enables them. Use More for
                        announcements.
                    </Text>
                </Card>
                <TouchableOpacity
                    style={[styles.link, { backgroundColor: isDark ? colors.surfaceDark : BRAND.surface, borderColor: isDark ? colors.borderDark : BRAND.border }]}
                    onPress={() => navigation.getParent()?.navigate('StudentMoreTab', { screen: 'Announcements' })}
                >
                    <Icon name="campaign" size={22} color={colors.primary} />
                    <Text style={[styles.linkText, { color: textMain }]}>Announcements</Text>
                    <Icon name="chevron-right" size={22} color={textSub} />
                </TouchableOpacity>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    scroll: { padding: SPACING.xl },
    greet: { fontSize: FONT_SIZES.sm },
    name: { fontSize: FONT_SIZES.xxl, fontWeight: '700', marginBottom: SPACING.lg },
    body: { fontSize: FONT_SIZES.sm, lineHeight: 22 },
    link: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: SPACING.md,
        borderRadius: RADIUS.md,
        borderWidth: 1,
        marginTop: SPACING.lg,
        gap: SPACING.md,
    },
    linkText: { flex: 1, fontSize: FONT_SIZES.md, fontWeight: '600' },
});
