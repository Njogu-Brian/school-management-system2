import React, { useState, useEffect, useRef } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    ScrollView,
    KeyboardAvoidingView,
    Platform,
    TouchableOpacity,
    TextInput,
    Alert,
    ActivityIndicator,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { Button } from '@components/common/Button';
import { hrApi } from '@api/hr.api';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { layoutStyles } from '@styles/common';
import { canManageStaff } from '@utils/staffHrAccess';
import DateTimePicker, { DateTimePickerEvent } from '@react-native-community/datetimepicker';
import Icon from 'react-native-vector-icons/MaterialIcons';

type LeaveTypeOption = { id: number; name: string; code?: string; max_days?: number };

interface ApplyLeaveScreenProps {
    navigation: any;
    route: { params?: { staffId?: number } };
}

export const ApplyLeaveScreen: React.FC<ApplyLeaveScreenProps> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const paramStaffId = route.params?.staffId;
    const manageStaff = canManageStaff(user);

    const [types, setTypes] = useState<LeaveTypeOption[]>([]);
    const [loadingTypes, setLoadingTypes] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [selectedTypeId, setSelectedTypeId] = useState<number | null>(null);
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [reason, setReason] = useState('');
    const [showStartPicker, setShowStartPicker] = useState(false);
    const [showEndPicker, setShowEndPicker] = useState(false);

    const toIso = (d: Date) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    const parseIso = (iso: string): Date => {
        const parts = iso?.split('-');
        if (parts?.length === 3) {
            const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            if (!isNaN(d.getTime())) return d;
        }
        return new Date();
    };

    const onChangeStart = (event: DateTimePickerEvent, date?: Date) => {
        setShowStartPicker(Platform.OS === 'ios');
        if (event.type === 'set' && date) setStartDate(toIso(date));
    };
    const onChangeEnd = (event: DateTimePickerEvent, date?: Date) => {
        setShowEndPicker(Platform.OS === 'ios');
        if (event.type === 'set' && date) setEndDate(toIso(date));
    };
    const [staffIdInput, setStaffIdInput] = useState(paramStaffId != null ? String(paramStaffId) : '');
    const prefilledStaffId = useRef(false);

    useEffect(() => {
        if (paramStaffId != null) {
            setStaffIdInput(String(paramStaffId));
        }
    }, [paramStaffId]);

    useEffect(() => {
        if (prefilledStaffId.current || paramStaffId != null) return;
        if (manageStaff && user?.staff_id) {
            setStaffIdInput(String(user.staff_id));
            prefilledStaffId.current = true;
        }
    }, [manageStaff, user?.staff_id, paramStaffId]);

    useEffect(() => {
        (async () => {
            try {
                const res = await hrApi.getLeaveTypes();
                if (res.success && res.data?.length) {
                    setTypes(res.data);
                    setSelectedTypeId(res.data[0].id);
                }
            } catch {
                Alert.alert('Error', 'Could not load leave types.');
            } finally {
                setLoadingTypes(false);
            }
        })();
    }, []);

    const onSubmit = async () => {
        if (!selectedTypeId) {
            Alert.alert('Validation', 'Select a leave type.');
            return;
        }
        if (!/^\d{4}-\d{2}-\d{2}$/.test(startDate) || !/^\d{4}-\d{2}-\d{2}$/.test(endDate)) {
            Alert.alert('Validation', 'Use dates in YYYY-MM-DD format.');
            return;
        }

        const payload: Parameters<typeof hrApi.applyLeave>[0] = {
            leave_type_id: selectedTypeId,
            start_date: startDate,
            end_date: endDate,
            reason: reason.trim() || undefined,
        };

        if (manageStaff) {
            const sid = parseInt(staffIdInput.trim(), 10);
            if (!sid || Number.isNaN(sid)) {
                Alert.alert('Validation', 'Enter the staff ID this leave is for (HR must specify staff).');
                return;
            }
            payload.staff_id = sid;
        }

        try {
            setSubmitting(true);
            const res = await hrApi.applyLeave(payload);
            if (res.success) {
                Alert.alert('Submitted', 'Leave request submitted.', [
                    { text: 'OK', onPress: () => navigation.goBack() },
                ]);
            } else {
                Alert.alert('Error', (res as any).message || 'Request failed.');
            }
        } catch (e: any) {
            const msg = e?.response?.data?.message || e?.message || 'Could not submit leave.';
            Alert.alert('Error', typeof msg === 'string' ? msg : 'Could not submit leave.');
        } finally {
            setSubmitting(false);
        }
    };

    const border = isDark ? colors.borderDark : colors.borderLight;
    const mainText = isDark ? colors.textMainDark : colors.textMainLight;
    const subText = isDark ? colors.textSubDark : colors.textSubLight;

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <KeyboardAvoidingView
                style={layoutStyles.flex1}
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
                keyboardVerticalOffset={0}
            >
                {loadingTypes ? (
                    <View style={styles.centered}>
                        <ActivityIndicator color={colors.primary} />
                    </View>
                ) : types.length === 0 ? (
                    <Text style={[styles.hint, { color: subText, paddingHorizontal: SPACING.xl }]}>
                        No active leave types are configured. Ask an administrator to set up leave types in the portal.
                    </Text>
                ) : (
                    <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
                        <Card style={styles.card}>
                            <Text style={[styles.label, { color: mainText }]}>Leave type</Text>
                            {types.map((t) => (
                                <TouchableOpacity
                                    key={t.id}
                                    style={[
                                        styles.typeRow,
                                        { borderColor: border },
                                        selectedTypeId === t.id && { borderColor: colors.primary, backgroundColor: colors.primary + '12' },
                                    ]}
                                    onPress={() => setSelectedTypeId(t.id)}
                                >
                                    <Text style={[styles.typeName, { color: mainText }]}>{t.name}</Text>
                                    {t.max_days != null ? (
                                        <Text style={[styles.typeMeta, { color: subText }]}>Max {t.max_days} days</Text>
                                    ) : null}
                                </TouchableOpacity>
                            ))}
                        </Card>

                        {manageStaff ? (
                            <Card style={styles.card}>
                                <Text style={[styles.label, { color: mainText }]}>Staff ID</Text>
                                <Text style={[styles.hint, { color: subText }]}>
                                    Required for HR: the staff record ID (same as in the directory / URL).
                                </Text>
                                <TextInput
                                    style={[styles.input, { color: mainText, borderColor: border }]}
                                    value={staffIdInput}
                                    onChangeText={setStaffIdInput}
                                    placeholder="e.g. 42"
                                    placeholderTextColor={subText}
                                    keyboardType="number-pad"
                                />
                            </Card>
                        ) : null}

                        <Card style={styles.card}>
                            <Text style={[styles.label, { color: mainText }]}>Start date</Text>
                            <TouchableOpacity
                                style={[styles.dateButton, { borderColor: border }]}
                                onPress={() => setShowStartPicker(true)}
                            >
                                <Icon name="calendar-today" size={18} color={mainText} />
                                <Text style={[styles.dateButtonText, { color: startDate ? mainText : subText }]}>
                                    {startDate || 'Select start date'}
                                </Text>
                            </TouchableOpacity>
                            {showStartPicker ? (
                                <DateTimePicker
                                    value={startDate ? parseIso(startDate) : new Date()}
                                    mode="date"
                                    display={Platform.OS === 'ios' ? 'inline' : 'default'}
                                    onChange={onChangeStart}
                                />
                            ) : null}

                            <Text style={[styles.label, { color: mainText, marginTop: SPACING.md }]}>End date</Text>
                            <TouchableOpacity
                                style={[styles.dateButton, { borderColor: border }]}
                                onPress={() => setShowEndPicker(true)}
                            >
                                <Icon name="event" size={18} color={mainText} />
                                <Text style={[styles.dateButtonText, { color: endDate ? mainText : subText }]}>
                                    {endDate || 'Select end date'}
                                </Text>
                            </TouchableOpacity>
                            {showEndPicker ? (
                                <DateTimePicker
                                    value={endDate ? parseIso(endDate) : (startDate ? parseIso(startDate) : new Date())}
                                    mode="date"
                                    display={Platform.OS === 'ios' ? 'inline' : 'default'}
                                    minimumDate={startDate ? parseIso(startDate) : undefined}
                                    onChange={onChangeEnd}
                                />
                            ) : null}
                        </Card>

                        <Card style={styles.card}>
                            <Text style={[styles.label, { color: mainText }]}>Reason (optional)</Text>
                            <TextInput
                                style={[styles.textArea, { color: mainText, borderColor: border }]}
                                value={reason}
                                onChangeText={setReason}
                                placeholder="Brief reason"
                                placeholderTextColor={subText}
                                multiline
                                numberOfLines={4}
                            />
                        </Card>

                        <Button title={submitting ? 'Submitting…' : 'Submit request'} onPress={onSubmit} disabled={submitting} />
                    </ScrollView>
                )}
            </KeyboardAvoidingView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    content: { padding: SPACING.xl, paddingTop: SPACING.md, paddingBottom: SPACING.xxl },
    card: { marginBottom: SPACING.md },
    label: { fontSize: FONT_SIZES.sm, fontWeight: '700', marginBottom: SPACING.sm },
    hint: { fontSize: FONT_SIZES.xs, marginBottom: SPACING.sm, lineHeight: 18 },
    typeRow: {
        padding: SPACING.md,
        borderWidth: 1,
        borderRadius: 8,
        marginBottom: SPACING.sm,
    },
    typeName: { fontSize: FONT_SIZES.md, fontWeight: '600' },
    typeMeta: { fontSize: FONT_SIZES.xs, marginTop: 4 },
    input: {
        borderWidth: 1,
        borderRadius: 8,
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        fontSize: FONT_SIZES.md,
    },
    textArea: {
        borderWidth: 1,
        borderRadius: 8,
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        fontSize: FONT_SIZES.md,
        minHeight: 100,
        textAlignVertical: 'top',
    },
    centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    dateButton: {
        flexDirection: 'row',
        alignItems: 'center',
        borderWidth: 1,
        borderRadius: 8,
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.md,
    },
    dateButtonText: { marginLeft: SPACING.sm, fontSize: FONT_SIZES.md },
});
