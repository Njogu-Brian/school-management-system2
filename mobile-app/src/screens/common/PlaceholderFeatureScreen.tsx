import React from 'react';
import { View, Text, StyleSheet, SafeAreaView, TouchableOpacity } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { EmptyState } from '@components/common/EmptyState';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

const LABELS: Record<string, string> = {
    AttendanceRecords: 'Attendance records',
    AttendanceAnalytics: 'Attendance analytics',
    CreateExam: 'Create exam',
    ViewMarks: 'View marks',
    AddStaff: 'Add staff',
    AddRoute: 'Add route',
    Vehicles: 'Vehicles',
    Trips: 'Trips',
    BookDetail: 'Book details',
    AddBook: 'Add book',
    Borrowings: 'Borrowings',
    LibraryCards: 'Library cards',
    AnnouncementDetail: 'Announcement',
    Messages: 'Messages',
};

/**
 * Non-blank placeholder for flows not yet built in the mobile app (use web portal).
 */
export const PlaceholderFeatureScreen: React.FC<{ navigation: { goBack: () => void }; route: { name: string } }> = ({
    navigation,
    route,
}) => {
    const { isDark, colors } = useTheme();
    const title = LABELS[route.name] || 'This screen';
    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
            <View style={styles.topBar}>
                <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12} style={styles.back}>
                    <Icon name="arrow-back" size={24} color={colors.primary} />
                </TouchableOpacity>
            </View>
            <EmptyState
                accent="info"
                icon="construction"
                title={title}
                message="This workflow is not available in the mobile app yet. Please use the web portal for full features."
            />
            <Text style={[styles.footer, { color: textSub }]}>
                You can still use other tabs; nothing is wrong with your account.
            </Text>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    topBar: {
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
    },
    back: { alignSelf: 'flex-start', padding: SPACING.sm },
    footer: {
        textAlign: 'center',
        paddingHorizontal: SPACING.xl,
        fontSize: FONT_SIZES.sm,
        lineHeight: 20,
        marginTop: SPACING.md,
    },
});
