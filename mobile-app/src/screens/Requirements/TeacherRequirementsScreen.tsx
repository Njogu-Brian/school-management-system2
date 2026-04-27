import React, { useCallback, useEffect, useState } from 'react';
import {
    ActivityIndicator,
    FlatList,
    RefreshControl,
    StyleSheet,
    Text,
    TextInput,
    TouchableOpacity,
    View,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES } from '@constants/theme';
import {
    teacherRequirementsApi,
    RequirementStudent,
} from '@api/teacherRequirements.api';

export const TeacherRequirementsScreen: React.FC = () => {
    const navigation = useNavigation<any>();
    const { isDark, colors } = useTheme();

    const [students, setStudents] = useState<RequirementStudent[]>([]);
    const [loading, setLoading] = useState(false);
    const [refreshing, setRefreshing] = useState(false);
    const [search, setSearch] = useState('');
    const [error, setError] = useState<string | null>(null);

    const load = useCallback(async (opts: { silent?: boolean } = {}) => {
        try {
            if (!opts.silent) setLoading(true);
            setError(null);
            const res = await teacherRequirementsApi.getStudents({
                search: search.trim() || undefined,
                per_page: 30,
            });
            if (res.success && res.data) {
                setStudents(res.data.data ?? []);
            } else {
                setError(res.message || 'Unable to load students.');
            }
        } catch (e: any) {
            setError(e?.message || 'Unable to load students.');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [search]);

    useEffect(() => {
        load();
    }, []);

    const onRefresh = () => {
        setRefreshing(true);
        load({ silent: true });
    };

    const renderItem = ({ item }: { item: RequirementStudent }) => {
        const disabled = !item.can_teacher_receive;
        return (
            <TouchableOpacity
                style={[
                    styles.card,
                    {
                        backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                        borderColor: isDark ? colors.borderDark : colors.borderLight,
                        opacity: disabled ? 0.55 : 1,
                    },
                ]}
                disabled={disabled}
                onPress={() => navigation.navigate('TeacherRequirementDetail', { studentId: item.id })}
            >
                <View style={styles.cardRow}>
                    <View style={[styles.avatar, { backgroundColor: colors.primary + '20' }]}>
                        <Text style={{ color: colors.primary, fontWeight: '700' }}>
                            {item.full_name?.slice(0, 1)?.toUpperCase() ?? '?'}
                        </Text>
                    </View>
                    <View style={{ flex: 1 }}>
                        <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>{item.full_name}</Text>
                        <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.admission_number}{item.class_name ? ` • ${item.class_name}` : ''}
                            {item.stream_name ? ` / ${item.stream_name}` : ''}
                        </Text>
                        {item.is_new_joiner ? (
                            <Text style={[styles.badge, { backgroundColor: '#FFE0B2', color: '#E65100' }]}>New joiner</Text>
                        ) : null}
                        {disabled ? (
                            <Text style={[styles.hint, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Admin only (new-joiner requirements)
                            </Text>
                        ) : null}
                    </View>
                    <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
                </View>
            </TouchableOpacity>
        );
    };

    return (
        <View style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={[styles.searchBar, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight, borderColor: isDark ? colors.borderDark : colors.borderLight }]}>
                <Icon name="search" size={20} color={isDark ? colors.textSubDark : colors.textSubLight} />
                <TextInput
                    value={search}
                    onChangeText={setSearch}
                    placeholder="Search by name or admission number"
                    placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                    style={[styles.searchInput, { color: isDark ? colors.textMainDark : colors.textMainLight }]}
                    returnKeyType="search"
                    onSubmitEditing={() => load()}
                />
                {search ? (
                    <TouchableOpacity onPress={() => { setSearch(''); load(); }}>
                        <Icon name="close" size={20} color={isDark ? colors.textSubDark : colors.textSubLight} />
                    </TouchableOpacity>
                ) : null}
            </View>

            {loading ? (
                <View style={styles.center}>
                    <ActivityIndicator color={colors.primary} />
                </View>
            ) : error ? (
                <View style={styles.center}>
                    <Icon name="error-outline" size={36} color={colors.primary} />
                    <Text style={{ color: isDark ? colors.textMainDark : colors.textMainLight, marginTop: 8 }}>{error}</Text>
                    <TouchableOpacity style={[styles.retryBtn, { backgroundColor: colors.primary }]} onPress={() => load()}>
                        <Text style={{ color: '#fff', fontWeight: '600' }}>Retry</Text>
                    </TouchableOpacity>
                </View>
            ) : (
                <FlatList
                    data={students}
                    keyExtractor={(it) => String(it.id)}
                    renderItem={renderItem}
                    contentContainerStyle={{ padding: SPACING.md }}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />}
                    ListEmptyComponent={(
                        <View style={styles.center}>
                            <Icon name="inbox" size={40} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={{ color: isDark ? colors.textSubDark : colors.textSubLight, marginTop: 8 }}>No students found.</Text>
                        </View>
                    )}
                />
            )}
        </View>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    searchBar: {
        flexDirection: 'row',
        alignItems: 'center',
        margin: SPACING.md,
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        borderWidth: 1,
        borderRadius: 12,
    },
    searchInput: { flex: 1, marginLeft: SPACING.sm, fontSize: FONT_SIZES.md },
    card: {
        padding: SPACING.md,
        borderRadius: 12,
        borderWidth: 1,
        marginBottom: SPACING.sm,
    },
    cardRow: { flexDirection: 'row', alignItems: 'center' },
    avatar: {
        width: 40,
        height: 40,
        borderRadius: 20,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: SPACING.md,
    },
    name: { fontSize: FONT_SIZES.md, fontWeight: '600' },
    meta: { fontSize: FONT_SIZES.sm, marginTop: 2 },
    badge: {
        alignSelf: 'flex-start',
        marginTop: 6,
        paddingHorizontal: 8,
        paddingVertical: 2,
        borderRadius: 10,
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
        overflow: 'hidden',
    },
    hint: { fontSize: FONT_SIZES.xs, marginTop: 4, fontStyle: 'italic' },
    center: { alignItems: 'center', justifyContent: 'center', padding: SPACING.xl, flex: 1 },
    retryBtn: { marginTop: SPACING.md, paddingHorizontal: SPACING.lg, paddingVertical: SPACING.sm, borderRadius: 8 },
});
