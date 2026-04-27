import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity, RefreshControl } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { layoutStyles } from '@styles/common';
import { academicReportsApi } from '@api/academicReports.api';
import type { AcademicReportListItem } from '@types/academicReports.types';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { SPACING, BORDER_RADIUS, FONT_SIZES } from '@constants/theme';
import { LoadErrorBanner } from '@components/common/LoadErrorBanner';
import { EmptyState } from '@components/common/EmptyState';
import { ListLoadingSkeleton } from '@components/common/ListLoadingSkeleton';
import { useNavigation } from '@react-navigation/native';

export const AcademicReportsListScreen = () => {
    const { isDark, colors } = useTheme();
    const navigation = useNavigation<any>();
    const [items, setItems] = useState<AcademicReportListItem[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const load = async () => {
        try {
            setError(null);
            const data = await academicReportsApi.getAssigned();
            setItems(data);
        } catch (e: any) {
            setError(e?.message ?? 'Failed to load reports.');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    const onRefresh = async () => {
        setRefreshing(true);
        await load();
    };

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>Academic Reports</Text>
                <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                    Fill and submit assigned questionnaires.
                </Text>
            </View>

            {error ? <LoadErrorBanner message={error} onRetry={load} /> : null}

            {loading ? (
                <View style={{ paddingHorizontal: SPACING.xl }}>
                    <ListLoadingSkeleton />
                </View>
            ) : items.length === 0 ? (
                <View style={{ paddingHorizontal: SPACING.xl }}>
                    <EmptyState
                        title="No reports yet"
                        message="When a report is assigned to you, it will appear here."
                    />
                </View>
            ) : (
                <ScrollView
                    contentContainerStyle={{ padding: SPACING.xl, paddingTop: SPACING.md }}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                >
                    {items.map((it) => (
                        <TouchableOpacity
                            key={it.id}
                            style={[
                                styles.card,
                                {
                                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                    borderColor: isDark ? colors.borderDark : colors.borderLight,
                                },
                            ]}
                            activeOpacity={0.8}
                            onPress={() => navigation.navigate('AcademicReportFill', { templateId: it.id })}
                        >
                            <View style={{ flex: 1 }}>
                                <Text style={[styles.cardTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {it.title}
                                </Text>
                                {it.description ? (
                                    <Text
                                        style={[styles.cardDesc, { color: isDark ? colors.textSubDark : colors.textSubLight }]}
                                        numberOfLines={2}
                                    >
                                        {it.description}
                                    </Text>
                                ) : null}
                                <Text style={[styles.cardMeta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    {it.questions_count ?? 0} questions
                                </Text>
                            </View>
                            <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        </TouchableOpacity>
                    ))}
                </ScrollView>
            )}
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    header: { paddingHorizontal: SPACING.xl, paddingTop: SPACING.md, paddingBottom: SPACING.md },
    title: { fontSize: FONT_SIZES.xl, fontWeight: '700' },
    subtitle: { marginTop: 2, fontSize: FONT_SIZES.sm },
    card: {
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.lg,
        padding: SPACING.md,
        marginBottom: SPACING.sm,
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
    },
    cardTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700' },
    cardDesc: { marginTop: 4, fontSize: FONT_SIZES.sm, lineHeight: 18 },
    cardMeta: { marginTop: 8, fontSize: FONT_SIZES.sm, fontWeight: '600' },
});

