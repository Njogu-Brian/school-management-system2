import React, { useState, useEffect, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    FlatList,
    SafeAreaView,
    TouchableOpacity,
    RefreshControl,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { seniorTeacherApi, SupervisedStaffMember } from '@api/seniorTeacher.api';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface SupervisedStaffScreenProps {
    navigation: any;
}

export const SupervisedStaffScreen: React.FC<SupervisedStaffScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [staff, setStaff] = useState<SupervisedStaffMember[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const load = useCallback(async () => {
        try {
            setLoading(true);
            const response = await seniorTeacherApi.getSupervisedStaff();
            if (response.success && response.data) {
                const data = response.data as any;
                setStaff(Array.isArray(data) ? data : data?.data ?? []);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load supervised staff');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, []);

    const handleRefresh = () => {
        setRefreshing(true);
        load();
    };

    if (loading && !refreshing) {
        return <LoadingState message="Loading supervised staff..." />;
    }

    const list = Array.isArray(staff) ? staff : [];

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Supervised Staff
                </Text>
            </View>
            {list.length === 0 ? (
                <EmptyState
                    icon="people"
                    title="No supervised staff"
                    message="Staff you supervise will appear here."
                />
            ) : (
                <FlatList
                    data={list}
                    keyExtractor={(item) => String(item.id)}
                    contentContainerStyle={styles.list}
                    refreshControl={
                        <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} />
                    }
                    renderItem={({ item }) => (
                        <Card>
                            <View style={styles.row}>
                                <View style={styles.info}>
                                    <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {item.full_name}
                                    </Text>
                                    {(item.designation || item.department) && (
                                        <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {[item.designation, item.department].filter(Boolean).join(' • ')}
                                        </Text>
                                    )}
                                    {item.email && (
                                        <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {item.email}
                                        </Text>
                                    )}
                                </View>
                                <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            </View>
                        </Card>
                    )}
                />
            )}
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm },
    backBtn: { marginRight: SPACING.sm },
    title: { fontSize: FONT_SIZES.xl, fontWeight: 'bold' },
    list: { padding: SPACING.md, paddingBottom: SPACING.xxl },
    row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
    info: { flex: 1 },
    name: { fontSize: FONT_SIZES.lg, fontWeight: '600', marginBottom: 4 },
    meta: { fontSize: FONT_SIZES.sm, marginBottom: 2 },
});
