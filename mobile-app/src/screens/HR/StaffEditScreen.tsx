import React, { useState, useEffect, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    Alert,
    ActivityIndicator,
    Image,
    Platform,
} from 'react-native';
import { pickImageFromLibrary, PickedImageFile } from '@utils/pickMedia';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { UserRole } from '@constants/roles';
import { Card } from '@components/common/Card';
import { Input } from '@components/common/Input';
import { hrApi } from '@api/hr.api';
import { Staff } from '@types/hr.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { Palette } from '@styles/palette';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { goBack: () => void };
    route: { params: { staffId: number } };
}

export const StaffEditScreen: React.FC<Props> = ({ navigation, route }) => {
    const staffId = route.params.staffId;
    const { user } = useAuth();
    const canEditSalary =
        !!user && [UserRole.SUPER_ADMIN, UserRole.ADMIN, UserRole.SECRETARY].includes(user.role);
    const { isDark, colors } = useTheme();
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState(false);
    const [saving, setSaving] = useState(false);
    const [staff, setStaff] = useState<Staff | null>(null);

    const [form, setForm] = useState({
        first_name: '',
        last_name: '',
        middle_name: '',
        work_email: '',
        personal_email: '',
        phone_number: '',
        id_number: '',
        residential_address: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        bank_name: '',
        bank_branch: '',
        bank_account: '',
        basic_salary: '',
    });

    const [pickedAsset, setPickedAsset] = useState<PickedImageFile | null>(null);

    const bg = isDark ? colors.backgroundDark : colors.backgroundLight;
    const textMain = isDark ? colors.textMainDark : colors.textMainLight;
    const textSub = isDark ? colors.textSubDark : colors.textSubLight;

    const load = useCallback(async () => {
        try {
            setLoading(true);
            setLoadError(false);
            const res = await hrApi.getStaffMember(staffId);
            if (res.success && res.data) {
                const s = res.data as Staff;
                setStaff(s);
                setForm({
                    first_name: s.first_name ?? '',
                    last_name: s.last_name ?? '',
                    middle_name: s.middle_name ?? '',
                    work_email: (s as any).work_email ?? s.email ?? '',
                    personal_email: (s as any).personal_email ?? '',
                    phone_number: s.phone ?? (s as any).phone_number ?? '',
                    id_number: (s as any).id_number ?? '',
                    residential_address: (s as any).residential_address ?? s.address ?? '',
                    emergency_contact_name: (s as any).emergency_contact_name ?? '',
                    emergency_contact_phone: (s as any).emergency_contact_phone ?? '',
                    bank_name: (s as any).bank_name ?? '',
                    bank_branch: (s as any).bank_branch ?? '',
                    bank_account: (s as any).bank_account ?? '',
                    basic_salary:
                        (s as any).basic_salary != null ? String((s as any).basic_salary) : '',
                });
            } else {
                setLoadError(true);
            }
        } catch (e: any) {
            setLoadError(true);
            Alert.alert('Staff', e?.message || 'Failed to load');
        } finally {
            setLoading(false);
        }
    }, [staffId]);

    useEffect(() => {
        load();
    }, [load]);

    const pickPhoto = async () => {
        const picked = await pickImageFromLibrary({
            permissionDeniedMessage:
                'Allow photo library access to upload a staff profile photo.',
        });
        if (picked) setPickedAsset(picked);
    };

    const save = async () => {
        try {
            setSaving(true);
            let basic: number | undefined;
            if (canEditSalary && form.basic_salary.trim() !== '') {
                const parsed = parseFloat(form.basic_salary.replace(/,/g, ''));
                if (Number.isNaN(parsed)) {
                    Alert.alert('Validation', 'Basic salary must be a number.');
                    setSaving(false);
                    return;
                }
                basic = parsed;
            }
            const payload: Parameters<typeof hrApi.updateStaff>[1] = {
                first_name: form.first_name.trim(),
                last_name: form.last_name.trim(),
                middle_name: form.middle_name.trim() || undefined,
                work_email: form.work_email.trim(),
                personal_email: form.personal_email.trim() || undefined,
                phone_number: form.phone_number.trim(),
                id_number: form.id_number.trim(),
                residential_address: form.residential_address.trim() || undefined,
                emergency_contact_name: form.emergency_contact_name.trim() || undefined,
                emergency_contact_phone: form.emergency_contact_phone.trim() || undefined,
                bank_name: form.bank_name.trim() || undefined,
                bank_branch: form.bank_branch.trim() || undefined,
                bank_account: form.bank_account.trim() || undefined,
            };
            if (canEditSalary && basic !== undefined) {
                payload.basic_salary = basic;
            }
            const res = await hrApi.updateStaff(staffId, payload);
            if (!res.success) {
                Alert.alert('Save', (res as any).message || 'Failed');
                return;
            }
            if (pickedAsset?.uri) {
                const fd = new FormData();
                const rawName = pickedAsset.name?.trim() || 'photo.jpg';
                const fileName = rawName.includes('.') ? rawName : `${rawName}.jpg`;
                const mime = pickedAsset.type || 'image/jpeg';
                fd.append(
                    'photo',
                    {
                        uri: Platform.OS === 'android' ? pickedAsset.uri : pickedAsset.uri,
                        name: fileName,
                        type: mime,
                    } as unknown as Blob
                );
                const up = await hrApi.uploadStaffPhoto(staffId, fd);
                if (!up.success) {
                    Alert.alert('Photo', (up as any).message || 'Profile saved but photo upload failed.');
                    navigation.goBack();
                    return;
                }
            }
            Alert.alert('Saved', 'Staff profile updated.');
            navigation.goBack();
        } catch (e: any) {
            Alert.alert('Save', e?.message || 'Failed');
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: bg }]}>
                <View style={styles.header}>
                    <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                        <Icon name="arrow-back" size={24} color={colors.primary} />
                    </TouchableOpacity>
                    <Text style={[styles.title, { color: textMain }]}>Edit staff</Text>
                    <View style={{ width: 40 }} />
                </View>
                <ActivityIndicator style={{ marginTop: SPACING.xl }} color={colors.primary} />
            </SafeAreaView>
        );
    }

    if (loadError || !staff) {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: bg }]}>
                <View style={styles.header}>
                    <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                        <Icon name="arrow-back" size={24} color={colors.primary} />
                    </TouchableOpacity>
                    <Text style={[styles.title, { color: textMain }]}>Edit staff</Text>
                    <View style={{ width: 40 }} />
                </View>
                <Text style={[styles.hint, { color: textSub, padding: SPACING.xl }]}>Could not load this staff member.</Text>
            </SafeAreaView>
        );
    }

    const displayAvatar = pickedAsset?.uri ?? staff.avatar;

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: bg }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={colors.primary} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: textMain }]} numberOfLines={1}>
                    Edit staff
                </Text>
                <View style={{ width: 40 }} />
            </View>

            <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
                <Card style={{ backgroundColor: isDark ? colors.surfaceDark : BRAND.surface }}>
                    <Text style={[styles.sectionTitle, { color: textMain }]}>Photo</Text>
                    <View style={styles.photoRow}>
                        {displayAvatar ? (
                            <Image source={{ uri: displayAvatar }} style={styles.photo} />
                        ) : (
                            <View style={[styles.photoPlaceholder, { borderColor: BRAND.border }]}>
                                <Icon name="person" size={48} color={textSub} />
                            </View>
                        )}
                        <TouchableOpacity
                            style={[styles.pickBtn, { borderColor: colors.primary }]}
                            onPress={pickPhoto}
                        >
                            <Icon name="photo-camera" size={20} color={colors.primary} />
                            <Text style={{ color: colors.primary, fontWeight: '600' }}>Choose photo</Text>
                        </TouchableOpacity>
                    </View>
                </Card>

                <Card style={{ backgroundColor: isDark ? colors.surfaceDark : BRAND.surface }}>
                    <Text style={[styles.sectionTitle, { color: textMain }]}>Names & contacts</Text>
                    <Input
                        label="First name"
                        value={form.first_name}
                        onChangeText={(t) => setForm((f) => ({ ...f, first_name: t }))}
                    />
                    <Input
                        label="Middle name"
                        value={form.middle_name}
                        onChangeText={(t) => setForm((f) => ({ ...f, middle_name: t }))}
                    />
                    <Input
                        label="Last name"
                        value={form.last_name}
                        onChangeText={(t) => setForm((f) => ({ ...f, last_name: t }))}
                    />
                    <Input
                        label="Work email"
                        value={form.work_email}
                        onChangeText={(t) => setForm((f) => ({ ...f, work_email: t }))}
                        keyboardType="email-address"
                        autoCapitalize="none"
                    />
                    <Input
                        label="Personal email"
                        value={form.personal_email}
                        onChangeText={(t) => setForm((f) => ({ ...f, personal_email: t }))}
                        keyboardType="email-address"
                        autoCapitalize="none"
                    />
                    <Input
                        label="Phone"
                        value={form.phone_number}
                        onChangeText={(t) => setForm((f) => ({ ...f, phone_number: t }))}
                        keyboardType="phone-pad"
                    />
                    <Input
                        label="ID number"
                        value={form.id_number}
                        onChangeText={(t) => setForm((f) => ({ ...f, id_number: t }))}
                    />
                </Card>

                <Card style={{ backgroundColor: isDark ? colors.surfaceDark : BRAND.surface }}>
                    <Text style={[styles.sectionTitle, { color: textMain }]}>Address & emergency</Text>
                    <Input
                        label="Residential address"
                        value={form.residential_address}
                        onChangeText={(t) => setForm((f) => ({ ...f, residential_address: t }))}
                        multiline
                    />
                    <Input
                        label="Emergency contact name"
                        value={form.emergency_contact_name}
                        onChangeText={(t) => setForm((f) => ({ ...f, emergency_contact_name: t }))}
                    />
                    <Input
                        label="Emergency contact phone"
                        value={form.emergency_contact_phone}
                        onChangeText={(t) => setForm((f) => ({ ...f, emergency_contact_phone: t }))}
                        keyboardType="phone-pad"
                    />
                </Card>

                <Card style={{ backgroundColor: isDark ? colors.surfaceDark : BRAND.surface }}>
                    <Text style={[styles.sectionTitle, { color: textMain }]}>Bank & salary</Text>
                    <Input
                        label="Bank name"
                        value={form.bank_name}
                        onChangeText={(t) => setForm((f) => ({ ...f, bank_name: t }))}
                    />
                    <Input
                        label="Branch"
                        value={form.bank_branch}
                        onChangeText={(t) => setForm((f) => ({ ...f, bank_branch: t }))}
                    />
                    <Input
                        label="Account number"
                        value={form.bank_account}
                        onChangeText={(t) => setForm((f) => ({ ...f, bank_account: t }))}
                    />
                    {canEditSalary ? (
                        <Input
                            label="Basic salary (optional)"
                            value={form.basic_salary}
                            onChangeText={(t) => setForm((f) => ({ ...f, basic_salary: t }))}
                            keyboardType="decimal-pad"
                        />
                    ) : null}
                </Card>

                <TouchableOpacity
                    style={[styles.saveBtn, { backgroundColor: colors.primary, opacity: saving ? 0.7 : 1 }]}
                    onPress={save}
                    disabled={saving}
                >
                    {saving ? (
                        <ActivityIndicator color={Palette.onPrimary} />
                    ) : (
                        <Text style={styles.saveBtnText}>Save changes</Text>
                    )}
                </TouchableOpacity>
                <Text style={[styles.hint, { color: textSub }]}>
                    Department, job title, and supervisor are managed in the web portal.
                </Text>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
    },
    backBtn: { padding: SPACING.sm },
    title: { flex: 1, fontSize: FONT_SIZES.lg, fontWeight: '700', textAlign: 'center' },
    scroll: { padding: SPACING.xl, paddingBottom: SPACING.xxl },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: '700', marginBottom: SPACING.sm },
    photoRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.md },
    photo: { width: 88, height: 88, borderRadius: RADIUS.card },
    photoPlaceholder: {
        width: 88,
        height: 88,
        borderRadius: RADIUS.card,
        borderWidth: 1,
        alignItems: 'center',
        justifyContent: 'center',
    },
    pickBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
        paddingVertical: SPACING.sm,
        paddingHorizontal: SPACING.md,
        borderWidth: 1,
        borderRadius: RADIUS.button,
    },
    saveBtn: {
        paddingVertical: SPACING.md,
        borderRadius: RADIUS.button,
        alignItems: 'center',
        marginTop: SPACING.md,
    },
    saveBtnText: { color: Palette.onPrimary, fontWeight: '700', fontSize: FONT_SIZES.md },
    hint: { fontSize: FONT_SIZES.sm, marginTop: SPACING.md, lineHeight: 20 },
});
