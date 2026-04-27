import React, { useCallback, useEffect, useState } from 'react';
import { Alert, FlatList, RefreshControl, SafeAreaView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useTheme } from '@contexts/ThemeContext';
import { academicsApi } from '@api/academics.api';
import { LessonPlan } from 'types/academics.types';
import { Card } from '@components/common/Card';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';

interface Props {
    navigation: any;
}

export const LessonPlanReviewQueueScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [plans, setPlans] = useState<LessonPlan[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const load = useCallback(async () => {
        try {
            setLoading(true);
            const res = await academicsApi.getLessonPlansReviewQueue({ per_page: 50 });
            if (res.success && res.data) {
                const data = res.data as any;
                setPlans(Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []);
            } else {
                setPlans([]);
            }
        } catch (e: any) {
            Alert.alert('Review queue', e?.message || 'Could not load review queue.');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : colors.textMainLight;
    const textSub = isDark ? colors.textSubDark : colors.textSubLight;

    if (loading && !refreshing) {
        return <LoadingState message="Loading review queue..." />;
    }

    const renderItem = ({ item }: { item: LessonPlan }) => (
        <Card onPress={() => navigation.navigate('LessonPlanDetail', { planId: item.id })}>
            <View style={styles.cardRow}>
                <View style={{ flex: 1 }}>
                    <Text style={[styles.title, { color: textMain }]} numberOfLines={2}>
                        {item.topic}
                    </Text>
                    <Text style={[styles.meta, { color: textSub }]} numberOfLines={2}>
                        {[item.teacher_name, item.class_name, item.subject_name].filter(Boolean).join(' · ')}
                    </Text>
                    <Text style={[styles.meta, { color: textSub }]}>
                        {item.date ? formatters.formatDate(item.date) : ''}
                        {item.is_late ? ' · Late' : ''}
                    </Text>
                </View>
                <Icon name="chevron-right" size={22} color={textSub} />
            </View>
        </Card>
    );

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={textMain} />
                </TouchableOpacity>
                <Text style={[styles.headerTitle, { color: textMain }]}>Lesson plan review</Text>
            </View>

            {plans.length === 0 ? (
                <EmptyState icon="inbox" title="No submissions" message="No submitted lesson plans awaiting review." />
            ) : (
                <FlatList
                    data={plans}
                    renderItem={renderItem}
                    keyExtractor={(i) => String(i.id)}
                    contentContainerStyle={styles.list}
                    refreshControl={
                        <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); void load(); }} colors={[colors.primary]} />
                    }
                />
            )}
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm },
    backBtn: { marginRight: SPACING.sm },
    headerTitle: { fontSize: FONT_SIZES.lg, fontWeight: '800' },
    list: { padding: SPACING.md, paddingBottom: SPACING.xxl, gap: SPACING.sm },
    cardRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: SPACING.sm },
    title: { fontSize: FONT_SIZES.md, fontWeight: '800' },
    meta: { fontSize: FONT_SIZES.sm, marginTop: 2 },
});

