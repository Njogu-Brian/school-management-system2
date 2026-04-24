import React, { useCallback, useEffect, useState } from 'react';
import {
    ActivityIndicator,
    Alert,
    FlatList,
    Modal,
    StyleSheet,
    Text,
    TextInput,
    TouchableOpacity,
    View,
} from 'react-native';
import { useRoute, useNavigation } from '@react-navigation/native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES } from '@constants/theme';
import {
    teacherRequirementsApi,
    RequirementItem,
    RequirementStudentDetail,
} from '@api/teacherRequirements.api';

export const TeacherRequirementDetailScreen: React.FC = () => {
    const route = useRoute<any>();
    const navigation = useNavigation<any>();
    const { isDark, colors } = useTheme();
    const studentId = route.params?.studentId as number;

    const [data, setData] = useState<RequirementStudentDetail | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selected, setSelected] = useState<RequirementItem | null>(null);
    const [qty, setQty] = useState('');
    const [notes, setNotes] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const load = useCallback(async () => {
        try {
            setLoading(true);
            setError(null);
            const res = await teacherRequirementsApi.getStudentTemplates(studentId);
            if (res.success && res.data) {
                setData(res.data);
            } else {
                setError(res.message || 'Unable to load requirements.');
            }
        } catch (e: any) {
            setError(e?.message || 'Unable to load requirements.');
        } finally {
            setLoading(false);
        }
    }, [studentId]);

    useEffect(() => {
        load();
    }, [load]);

    const openCollect = (item: RequirementItem) => {
        setSelected(item);
        const remaining = Math.max(item.quantity_required - item.quantity_collected, 0);
        setQty(remaining > 0 ? String(remaining) : '');
        setNotes('');
    };

    const submit = async () => {
        if (!selected) return;
        const n = parseFloat(qty);
        if (!isFinite(n) || n <= 0) {
            Alert.alert('Enter quantity', 'Please enter a valid quantity greater than zero.');
            return;
        }
        try {
            setSubmitting(true);
            const res = await teacherRequirementsApi.collect({
                student_id: studentId,
                template_id: selected.template_id,
                quantity_received: n,
                notes: notes.trim() || undefined,
            });
            if (res.success) {
                setSelected(null);
                await load();
                Alert.alert('Saved', 'Requirement recorded successfully.');
            } else {
                Alert.alert('Error', res.message || 'Could not record this receipt.');
            }
        } catch (e: any) {
            Alert.alert('Error', e?.message || 'Could not record this receipt.');
        } finally {
            setSubmitting(false);
        }
    };

    const renderItem = ({ item }: { item: RequirementItem }) => {
        const statusColor = item.status === 'complete' ? '#2E7D32' : item.status === 'partial' ? '#F57C00' : '#C62828';
        return (
            <View style={[
                styles.card,
                { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight, borderColor: isDark ? colors.borderDark : colors.borderLight },
            ]}>
                <View style={{ flex: 1 }}>
                    <Text style={[styles.title, { color: isDark ? colors.textDark : colors.textLight }]}>{item.name}</Text>
                    {item.brand ? (
                        <Text style={[styles.sub, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Brand: {item.brand}</Text>
                    ) : null}
                    <Text style={[styles.sub, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {item.quantity_collected}/{item.quantity_required} {item.unit || ''}
                    </Text>
                    <Text style={[styles.statusPill, { color: statusColor, borderColor: statusColor }]}>
                        {item.status.toUpperCase()}
                    </Text>
                </View>
                <TouchableOpacity
                    style={[styles.collectBtn, { backgroundColor: colors.primary }]}
                    onPress={() => openCollect(item)}
                    disabled={item.status === 'complete'}
                >
                    <Text style={{ color: '#fff', fontWeight: '600' }}>
                        {item.status === 'complete' ? 'Complete' : 'Receive'}
                    </Text>
                </TouchableOpacity>
            </View>
        );
    };

    if (loading) {
        return <View style={[styles.center, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight, flex: 1 }]}><ActivityIndicator color={colors.primary} /></View>;
    }

    if (error) {
        return (
            <View style={[styles.center, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight, flex: 1 }]}>
                <Icon name="error-outline" size={36} color={colors.primary} />
                <Text style={{ color: isDark ? colors.textDark : colors.textLight, marginTop: 8, textAlign: 'center' }}>{error}</Text>
                <TouchableOpacity style={[styles.retryBtn, { backgroundColor: colors.primary }]} onPress={() => navigation.goBack()}>
                    <Text style={{ color: '#fff', fontWeight: '600' }}>Go back</Text>
                </TouchableOpacity>
            </View>
        );
    }

    return (
        <View style={{ flex: 1, backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }}>
            {data?.student ? (
                <View style={[styles.header, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight, borderColor: isDark ? colors.borderDark : colors.borderLight }]}>
                    <Text style={[styles.studentName, { color: isDark ? colors.textDark : colors.textLight }]}>{data.student.full_name}</Text>
                    <Text style={[styles.sub, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {data.student.admission_number} • {data.student.class_name || 'No class'}
                    </Text>
                    {data.current_term ? (
                        <Text style={[styles.sub, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Term: {data.current_term.name}</Text>
                    ) : null}
                </View>
            ) : null}

            <FlatList
                data={data?.items ?? []}
                keyExtractor={(it) => String(it.template_id)}
                renderItem={renderItem}
                contentContainerStyle={{ padding: SPACING.md }}
                ListEmptyComponent={(
                    <View style={styles.center}>
                        <Icon name="assignment-turned-in" size={40} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        <Text style={{ color: isDark ? colors.textSubDark : colors.textSubLight, marginTop: 8 }}>No requirements for this student yet.</Text>
                    </View>
                )}
            />

            <Modal visible={!!selected} animationType="slide" transparent onRequestClose={() => setSelected(null)}>
                <View style={styles.modalBackdrop}>
                    <View style={[styles.modalCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                        <Text style={[styles.modalTitle, { color: isDark ? colors.textDark : colors.textLight }]}>
                            Receive: {selected?.name}
                        </Text>
                        <Text style={[styles.sub, { color: isDark ? colors.textSubDark : colors.textSubLight, marginBottom: SPACING.md }]}>
                            Currently: {selected?.quantity_collected}/{selected?.quantity_required} {selected?.unit || ''}
                        </Text>

                        <Text style={[styles.label, { color: isDark ? colors.textDark : colors.textLight }]}>Quantity received</Text>
                        <TextInput
                            value={qty}
                            onChangeText={setQty}
                            keyboardType="decimal-pad"
                            placeholder="e.g. 1"
                            placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                            style={[styles.input, { color: isDark ? colors.textDark : colors.textLight, borderColor: isDark ? colors.borderDark : colors.borderLight }]}
                        />

                        <Text style={[styles.label, { color: isDark ? colors.textDark : colors.textLight }]}>Notes (optional)</Text>
                        <TextInput
                            value={notes}
                            onChangeText={setNotes}
                            placeholder="Any comment"
                            placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                            style={[styles.input, { color: isDark ? colors.textDark : colors.textLight, borderColor: isDark ? colors.borderDark : colors.borderLight, minHeight: 60 }]}
                            multiline
                        />

                        <View style={styles.modalActions}>
                            <TouchableOpacity style={[styles.cancelBtn, { borderColor: isDark ? colors.borderDark : colors.borderLight }]} onPress={() => setSelected(null)} disabled={submitting}>
                                <Text style={{ color: isDark ? colors.textDark : colors.textLight }}>Cancel</Text>
                            </TouchableOpacity>
                            <TouchableOpacity style={[styles.saveBtn, { backgroundColor: colors.primary, opacity: submitting ? 0.7 : 1 }]} onPress={submit} disabled={submitting}>
                                <Text style={{ color: '#fff', fontWeight: '600' }}>{submitting ? 'Saving…' : 'Save'}</Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                </View>
            </Modal>
        </View>
    );
};

const styles = StyleSheet.create({
    header: { padding: SPACING.md, borderBottomWidth: 1 },
    studentName: { fontSize: FONT_SIZES.lg, fontWeight: '700' },
    sub: { fontSize: FONT_SIZES.sm, marginTop: 2 },
    card: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: SPACING.md,
        borderRadius: 12,
        borderWidth: 1,
        marginBottom: SPACING.sm,
    },
    title: { fontSize: FONT_SIZES.md, fontWeight: '600' },
    statusPill: {
        alignSelf: 'flex-start',
        marginTop: 6,
        paddingHorizontal: 8,
        paddingVertical: 2,
        borderRadius: 10,
        fontSize: FONT_SIZES.xs,
        fontWeight: '700',
        borderWidth: 1,
    },
    collectBtn: { paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm, borderRadius: 8, marginLeft: SPACING.md },
    center: { alignItems: 'center', justifyContent: 'center', padding: SPACING.xl },
    retryBtn: { marginTop: SPACING.md, paddingHorizontal: SPACING.lg, paddingVertical: SPACING.sm, borderRadius: 8 },
    modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
    modalCard: {
        padding: SPACING.lg,
        borderTopLeftRadius: 20,
        borderTopRightRadius: 20,
    },
    modalTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', marginBottom: SPACING.xs },
    label: { fontSize: FONT_SIZES.sm, marginTop: SPACING.sm, marginBottom: 4, fontWeight: '600' },
    input: {
        borderWidth: 1,
        borderRadius: 8,
        paddingHorizontal: SPACING.sm,
        paddingVertical: SPACING.sm,
    },
    modalActions: { flexDirection: 'row', justifyContent: 'flex-end', marginTop: SPACING.lg },
    cancelBtn: { paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm, borderRadius: 8, borderWidth: 1, marginRight: SPACING.sm },
    saveBtn: { paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm, borderRadius: 8 },
});
