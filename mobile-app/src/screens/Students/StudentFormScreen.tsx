import React, { useState, useEffect, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    KeyboardAvoidingView,
    Platform,
    Alert,
    TouchableOpacity,
    Modal,
    FlatList,
    ActivityIndicator,
} from 'react-native';
import * as DocumentPicker from 'expo-document-picker';
import { pickImageFromLibrary } from '@utils/pickMedia';
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Card } from '@components/common/Card';
import { Input } from '@components/common/Input';
import { studentsApi } from '@api/students.api';
import { Student, Class, Stream } from '@types/student.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

const DEFAULT_CC = '+254';

type PickedFile = { uri: string; name: string; type: string };

interface StudentFormScreenProps {
    navigation: { goBack: () => void };
    route: { params?: { studentId?: number } };
}

function appendIf(
    fd: FormData,
    key: string,
    value: string | number | boolean | null | undefined
): void {
    if (value === undefined || value === null) return;
    if (typeof value === 'string' && value.trim() === '') return;
    fd.append(key, String(value));
}

export const StudentFormScreen: React.FC<StudentFormScreenProps> = ({ navigation, route }) => {
    const studentId = route.params?.studentId;
    const isEdit = !!studentId;
    const { isDark, colors } = useTheme();

    const [loading, setLoading] = useState(false);
    const [loadingData, setLoadingData] = useState(isEdit);

    const [classes, setClasses] = useState<Class[]>([]);
    const [streams, setStreams] = useState<Stream[]>([]);
    const [categories, setCategories] = useState<{ id: number; name: string }[]>([]);

    const [classModal, setClassModal] = useState(false);
    const [streamModal, setStreamModal] = useState(false);
    const [catModal, setCatModal] = useState(false);

    const [photo, setPhoto] = useState<PickedFile | null>(null);
    const [fatherIdDoc, setFatherIdDoc] = useState<PickedFile | null>(null);
    const [motherIdDoc, setMotherIdDoc] = useState<PickedFile | null>(null);

    const [form, setForm] = useState({
        first_name: '',
        middle_name: '',
        last_name: '',
        gender: 'male',
        dob: '',
        classroom_id: '' as string,
        stream_id: '' as string,
        category_id: '' as string,
        father_name: '',
        mother_name: '',
        guardian_name: '',
        father_phone: '',
        mother_phone: '',
        guardian_phone: '',
        father_whatsapp: '',
        mother_whatsapp: '',
        guardian_whatsapp: '',
        father_email: '',
        mother_email: '',
        guardian_email: '',
        guardian_relationship: '',
        marital_status: '',
        father_id_number: '',
        mother_id_number: '',
        residential_area: '',
        preferred_hospital: '',
        nemis_number: '',
        knec_assessment_number: '',
        religion: '',
        has_allergies: false,
        allergies_notes: '',
        is_fully_immunized: '' as '' | '1' | '0',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        admission_date: '',
    });

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : colors.textMainLight;
    const textSub = isDark ? colors.textSubDark : colors.textSubLight;
    const surface = isDark ? colors.surfaceDark : BRAND.surface;

    const loadLookups = useCallback(async () => {
        try {
            const [cRes, catRes] = await Promise.all([
                studentsApi.getClasses(),
                studentsApi.getStudentCategories(),
            ]);
            if (cRes.success && cRes.data) setClasses(cRes.data);
            if (catRes.success && catRes.data) setCategories(catRes.data);
        } catch {
            Alert.alert('Error', 'Could not load classes or categories.');
        }
    }, []);

    const loadStreams = useCallback(async (classId: number) => {
        try {
            const res = await studentsApi.getStreams(classId);
            if (res.success && res.data) setStreams(res.data);
            else setStreams([]);
        } catch {
            setStreams([]);
        }
    }, []);

    useEffect(() => {
        loadLookups();
    }, [loadLookups]);

    useEffect(() => {
        const cid = parseInt(form.classroom_id, 10);
        if (cid > 0) loadStreams(cid);
        else setStreams([]);
    }, [form.classroom_id, loadStreams]);

    const fillFromStudent = useCallback((s: Student) => {
        const p = s.parent;
        setForm({
            first_name: s.first_name || '',
            middle_name: s.middle_name || '',
            last_name: s.last_name || '',
            gender: (s.gender as string) || 'male',
            dob: s.date_of_birth || '',
            classroom_id: String(s.class_id ?? s.classroom_id ?? ''),
            stream_id: s.stream_id ? String(s.stream_id) : '',
            category_id: s.category_id ? String(s.category_id) : '',
            father_name: p?.father_name || '',
            mother_name: p?.mother_name || '',
            guardian_name: p?.guardian_name || '',
            father_phone: p?.father_phone_local || '',
            mother_phone: p?.mother_phone_local || '',
            guardian_phone: p?.guardian_phone_local || '',
            father_whatsapp: p?.father_whatsapp_local || '',
            mother_whatsapp: p?.mother_whatsapp_local || '',
            guardian_whatsapp: p?.guardian_whatsapp_local || '',
            father_email: p?.father_email || '',
            mother_email: p?.mother_email || '',
            guardian_email: p?.guardian_email || '',
            guardian_relationship: p?.guardian_relationship || '',
            marital_status: p?.marital_status || '',
            father_id_number: p?.father_id_number || '',
            mother_id_number: p?.mother_id_number || '',
            residential_area: s.residential_area || s.address || '',
            preferred_hospital: s.preferred_hospital || '',
            nemis_number: s.nemis_number || '',
            knec_assessment_number: s.knec_assessment_number || '',
            religion: s.religion || '',
            has_allergies: !!s.has_allergies,
            allergies_notes: s.allergies_notes || '',
            is_fully_immunized:
                s.is_fully_immunized === true ? '1' : s.is_fully_immunized === false ? '0' : '',
            emergency_contact_name: s.emergency_contact_name || '',
            emergency_contact_phone: s.emergency_contact_phone_local || '',
            admission_date: s.admission_date || '',
        });
    }, []);

    useEffect(() => {
        if (!isEdit || !studentId) return;
        let cancelled = false;
        (async () => {
            try {
                setLoadingData(true);
                const res = await studentsApi.getStudent(studentId);
                if (!cancelled && res.success && res.data) fillFromStudent(res.data);
            } catch (e: any) {
                if (!cancelled) Alert.alert('Error', e.message || 'Failed to load student');
            } finally {
                if (!cancelled) setLoadingData(false);
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [isEdit, studentId, fillFromStudent]);

    const pickPhoto = async () => {
        const picked = await pickImageFromLibrary({
            quality: 0.85,
            permissionDeniedMessage:
                'Allow photo library access to upload the student photo.',
        });
        if (picked) setPhoto(picked);
    };

    const pickDoc = async (which: 'father' | 'mother') => {
        try {
            const result = await DocumentPicker.getDocumentAsync({
                type: ['application/pdf', 'image/*'],
                copyToCacheDirectory: true,
            });
            if (result.canceled || !result.assets?.[0]) return;
            const f = result.assets[0];
            const picked: PickedFile = {
                uri: f.uri,
                name: f.name || 'document.pdf',
                type: f.mimeType || 'application/pdf',
            };
            if (which === 'father') setFatherIdDoc(picked);
            else setMotherIdDoc(picked);
        } catch (err: unknown) {
            Alert.alert('Error', err instanceof Error ? err.message : 'Could not pick file');
        }
    };

    const buildFormData = (): FormData => {
        const fd = new FormData();
        appendIf(fd, 'first_name', form.first_name.trim());
        appendIf(fd, 'middle_name', form.middle_name.trim());
        appendIf(fd, 'last_name', form.last_name.trim());
        appendIf(fd, 'gender', form.gender);
        appendIf(fd, 'dob', form.dob.trim());
        appendIf(fd, 'classroom_id', form.classroom_id);
        if (form.stream_id) appendIf(fd, 'stream_id', form.stream_id);
        appendIf(fd, 'category_id', form.category_id);

        appendIf(fd, 'father_name', form.father_name.trim());
        appendIf(fd, 'mother_name', form.mother_name.trim());
        appendIf(fd, 'guardian_name', form.guardian_name.trim());
        appendIf(fd, 'father_phone', form.father_phone.trim());
        appendIf(fd, 'mother_phone', form.mother_phone.trim());
        appendIf(fd, 'guardian_phone', form.guardian_phone.trim());
        appendIf(fd, 'father_whatsapp', form.father_whatsapp.trim());
        appendIf(fd, 'mother_whatsapp', form.mother_whatsapp.trim());
        appendIf(fd, 'guardian_whatsapp', form.guardian_whatsapp.trim());
        appendIf(fd, 'father_email', form.father_email.trim());
        appendIf(fd, 'mother_email', form.mother_email.trim());
        appendIf(fd, 'guardian_email', form.guardian_email.trim());
        appendIf(fd, 'guardian_relationship', form.guardian_relationship.trim());
        appendIf(fd, 'marital_status', form.marital_status.trim());
        appendIf(fd, 'father_id_number', form.father_id_number.trim());
        appendIf(fd, 'mother_id_number', form.mother_id_number.trim());

        appendIf(fd, 'father_phone_country_code', DEFAULT_CC);
        appendIf(fd, 'mother_phone_country_code', DEFAULT_CC);
        appendIf(fd, 'guardian_phone_country_code', DEFAULT_CC);
        appendIf(fd, 'emergency_contact_country_code', DEFAULT_CC);

        appendIf(fd, 'residential_area', form.residential_area.trim());
        appendIf(fd, 'preferred_hospital', form.preferred_hospital.trim());
        appendIf(fd, 'nemis_number', form.nemis_number.trim());
        appendIf(fd, 'knec_assessment_number', form.knec_assessment_number.trim());
        appendIf(fd, 'religion', form.religion.trim());
        fd.append('has_allergies', form.has_allergies ? '1' : '0');
        appendIf(fd, 'allergies_notes', form.allergies_notes.trim());
        if (form.is_fully_immunized !== '') {
            fd.append('is_fully_immunized', form.is_fully_immunized);
        }
        appendIf(fd, 'emergency_contact_name', form.emergency_contact_name.trim());
        appendIf(fd, 'emergency_contact_phone', form.emergency_contact_phone.trim());
        appendIf(fd, 'admission_date', form.admission_date.trim());

        if (photo) {
            fd.append('photo', {
                uri: photo.uri,
                name: photo.name,
                type: photo.type,
            } as any);
        }
        if (fatherIdDoc) {
            fd.append('father_id_document', {
                uri: fatherIdDoc.uri,
                name: fatherIdDoc.name,
                type: fatherIdDoc.type,
            } as any);
        }
        if (motherIdDoc) {
            fd.append('mother_id_document', {
                uri: motherIdDoc.uri,
                name: motherIdDoc.name,
                type: motherIdDoc.type,
            } as any);
        }

        return fd;
    };

    const validate = (): string | null => {
        if (!form.first_name.trim() || !form.last_name.trim()) return 'First and last name are required.';
        if (!form.dob.trim()) return 'Date of birth is required.';
        if (!form.classroom_id) return 'Please select a class.';
        if (!form.category_id) return 'Please select a student category.';
        const parentName = form.father_name.trim() || form.mother_name.trim() || form.guardian_name.trim();
        const parentPhone =
            form.father_phone.trim() || form.mother_phone.trim() || form.guardian_phone.trim();
        if (!parentName || !parentPhone) {
            return 'At least one parent/guardian name and a local phone (digits only) are required.';
        }
        const digitOk = (s: string) => !s.trim() || /^[0-9]{4,15}$/.test(s.trim());
        if (!digitOk(form.father_phone)) return 'Father phone must be local digits only (4–15).';
        if (!digitOk(form.mother_phone)) return 'Mother phone must be local digits only (4–15).';
        if (!digitOk(form.guardian_phone)) return 'Guardian phone must be local digits only (4–15).';
        const waOk = (s: string) => !s.trim() || /^[0-9]{4,15}$/.test(s.trim());
        if (!waOk(form.father_whatsapp)) return 'Father WhatsApp must be local digits only (4–15).';
        if (!waOk(form.mother_whatsapp)) return 'Mother WhatsApp must be local digits only (4–15).';
        if (!waOk(form.guardian_whatsapp)) return 'Guardian WhatsApp must be local digits only (4–15).';
        if (form.emergency_contact_phone.trim() && !/^[0-9]{4,15}$/.test(form.emergency_contact_phone.trim())) {
            return 'Emergency phone must be local digits only (4–15).';
        }
        if (streams.length > 0 && !form.stream_id) return 'Please select a stream for this class.';
        if (isEdit && !form.residential_area.trim()) return 'Residential area is required when editing.';
        return null;
    };

    const handleSubmit = async () => {
        const err = validate();
        if (err) {
            Alert.alert('Validation', err);
            return;
        }
        setLoading(true);
        try {
            const fd = buildFormData();
            if (isEdit && studentId) {
                const res = await studentsApi.updateStudentMultipart(studentId, fd);
                if (res.success) {
                    Alert.alert('Success', 'Student updated.', [{ text: 'OK', onPress: () => navigation.goBack() }]);
                }
            } else {
                const res = await studentsApi.createStudentMultipart(fd);
                if (res.success) {
                    Alert.alert('Success', 'Student created.', [{ text: 'OK', onPress: () => navigation.goBack() }]);
                }
            }
        } catch (e: any) {
            const msg =
                typeof e?.message === 'string'
                    ? e.message
                    : Array.isArray(e?.errors)
                    ? JSON.stringify(e.errors)
                    : 'Request failed';
            Alert.alert('Error', msg);
        } finally {
            setLoading(false);
        }
    };

    const classLabel = () => {
        const id = parseInt(form.classroom_id, 10);
        const c = classes.find((x) => x.id === id);
        return c ? c.name : 'Select class';
    };

    const streamLabel = () => {
        const id = parseInt(form.stream_id, 10);
        const s = streams.find((x) => x.id === id);
        return s ? s.name : streams.length ? 'Select stream' : '—';
    };

    const catLabel = () => {
        const id = parseInt(form.category_id, 10);
        const c = categories.find((x) => x.id === id);
        return c ? c.name : 'Select category';
    };

    if (loadingData) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
                <ActivityIndicator size="large" color={BRAND.primary} style={{ marginTop: SPACING.xxl }} />
                <Text style={{ textAlign: 'center', marginTop: SPACING.md, color: textSub }}>Loading student…</Text>
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
            <KeyboardAvoidingView
                style={layoutStyles.flex1}
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
                keyboardVerticalOffset={0}
            >
            <ScrollView style={styles.scroll} contentContainerStyle={styles.scrollContent} keyboardShouldPersistTaps="handled">
                <Card style={[styles.card, { backgroundColor: surface, borderRadius: RADIUS.card }]}>
                    <Text style={[styles.sectionTitle, { color: textMain }]}>Student</Text>
                    <Input
                        label="First name *"
                        value={form.first_name}
                        onChangeText={(v) => setForm((f) => ({ ...f, first_name: v }))}
                    />
                    <Input
                        label="Middle name"
                        value={form.middle_name}
                        onChangeText={(v) => setForm((f) => ({ ...f, middle_name: v }))}
                    />
                    <Input
                        label="Last name *"
                        value={form.last_name}
                        onChangeText={(v) => setForm((f) => ({ ...f, last_name: v }))}
                    />
                    <Text style={[styles.label, { color: textSub }]}>Gender *</Text>
                    <View style={styles.chips}>
                        {(['male', 'female', 'other'] as const).map((g) => (
                            <TouchableOpacity
                                key={g}
                                style={[
                                    styles.chip,
                                    form.gender === g && { backgroundColor: BRAND.primary + '33', borderColor: BRAND.primary },
                                    { borderColor: isDark ? colors.borderDark : BRAND.border },
                                ]}
                                onPress={() => setForm((prev) => ({ ...prev, gender: g }))}
                            >
                                <Text style={{ color: textMain, textTransform: 'capitalize' }}>{g}</Text>
                            </TouchableOpacity>
                        ))}
                    </View>
                    <Input
                        label="Date of birth * (YYYY-MM-DD)"
                        value={form.dob}
                        onChangeText={(v) => setForm((f) => ({ ...f, dob: v }))}
                        placeholder="2015-01-15"
                    />
                    <TouchableOpacity style={[styles.selectBtn, { borderColor: isDark ? colors.borderDark : BRAND.border }]} onPress={() => setClassModal(true)}>
                        <Text style={{ color: textMain }}>Class: {classLabel()}</Text>
                        <Icon name="arrow-drop-down" size={24} color={textSub} />
                    </TouchableOpacity>
                    {streams.length > 0 && (
                        <TouchableOpacity style={[styles.selectBtn, { borderColor: isDark ? colors.borderDark : BRAND.border }]} onPress={() => setStreamModal(true)}>
                            <Text style={{ color: textMain }}>Stream: {streamLabel()}</Text>
                            <Icon name="arrow-drop-down" size={24} color={textSub} />
                        </TouchableOpacity>
                    )}
                    <TouchableOpacity style={[styles.selectBtn, { borderColor: isDark ? colors.borderDark : BRAND.border }]} onPress={() => setCatModal(true)}>
                        <Text style={{ color: textMain }}>Category: {catLabel()}</Text>
                        <Icon name="arrow-drop-down" size={24} color={textSub} />
                    </TouchableOpacity>
                    <TouchableOpacity style={styles.uploadRow} onPress={pickPhoto}>
                        <Icon name="photo-camera" size={22} color={BRAND.primary} />
                        <Text style={{ color: textMain, marginLeft: SPACING.sm }}>
                            {photo ? `Photo: ${photo.name}` : 'Student photo (optional)'}
                        </Text>
                    </TouchableOpacity>
                </Card>

                <Card style={[styles.card, { backgroundColor: surface, borderRadius: RADIUS.card }]}>
                    <Text style={[styles.sectionTitle, { color: textMain }]}>Parents / guardians</Text>
                    <Text style={[styles.hint, { color: textSub }]}>Phones: local digits only (e.g. 712345678).</Text>
                    <Input label="Father name" value={form.father_name} onChangeText={(v) => setForm((f) => ({ ...f, father_name: v }))} />
                    <Input label="Father phone" value={form.father_phone} onChangeText={(v) => setForm((f) => ({ ...f, father_phone: v }))} keyboardType="phone-pad" />
                    <Input label="Mother name" value={form.mother_name} onChangeText={(v) => setForm((f) => ({ ...f, mother_name: v }))} />
                    <Input label="Mother phone" value={form.mother_phone} onChangeText={(v) => setForm((f) => ({ ...f, mother_phone: v }))} keyboardType="phone-pad" />
                    <Input label="Guardian name" value={form.guardian_name} onChangeText={(v) => setForm((f) => ({ ...f, guardian_name: v }))} />
                    <Input label="Guardian phone" value={form.guardian_phone} onChangeText={(v) => setForm((f) => ({ ...f, guardian_phone: v }))} keyboardType="phone-pad" />
                    <Input label="Guardian relationship" value={form.guardian_relationship} onChangeText={(v) => setForm((f) => ({ ...f, guardian_relationship: v }))} />
                    <TouchableOpacity style={styles.uploadRow} onPress={() => pickDoc('father')}>
                        <Icon name="attach-file" size={22} color={BRAND.primary} />
                        <Text style={{ color: textMain, marginLeft: SPACING.sm }} numberOfLines={1}>
                            {fatherIdDoc ? `Father ID: ${fatherIdDoc.name}` : 'Father ID document (optional)'}
                        </Text>
                    </TouchableOpacity>
                    <TouchableOpacity style={styles.uploadRow} onPress={() => pickDoc('mother')}>
                        <Icon name="attach-file" size={22} color={BRAND.primary} />
                        <Text style={{ color: textMain, marginLeft: SPACING.sm }} numberOfLines={1}>
                            {motherIdDoc ? `Mother ID: ${motherIdDoc.name}` : 'Mother ID document (optional)'}
                        </Text>
                    </TouchableOpacity>
                </Card>

                <Card style={[styles.card, { backgroundColor: surface, borderRadius: RADIUS.card }]}>
                    <Text style={[styles.sectionTitle, { color: textMain }]}>Address & health</Text>
                    <Input
                        label={isEdit ? 'Residential area *' : 'Residential area'}
                        value={form.residential_area}
                        onChangeText={(v) => setForm((f) => ({ ...f, residential_area: v }))}
                    />
                    <Input label="Preferred hospital" value={form.preferred_hospital} onChangeText={(v) => setForm((f) => ({ ...f, preferred_hospital: v }))} />
                    <View style={styles.rowBetween}>
                        <Text style={{ color: textMain }}>Has allergies</Text>
                        <TouchableOpacity onPress={() => setForm((f) => ({ ...f, has_allergies: !f.has_allergies }))}>
                            <Icon name={form.has_allergies ? 'check-box' : 'check-box-outline-blank'} size={28} color={BRAND.primary} />
                        </TouchableOpacity>
                    </View>
                    <Input
                        label="Allergies notes"
                        value={form.allergies_notes}
                        onChangeText={(v) => setForm((f) => ({ ...f, allergies_notes: v }))}
                        multiline
                    />
                    <Text style={[styles.label, { color: textSub }]}>Fully immunized</Text>
                    <View style={styles.chips}>
                        {(['', '1', '0'] as const).map((v) => (
                            <TouchableOpacity
                                key={v || 'unset'}
                                style={[
                                    styles.chip,
                                    form.is_fully_immunized === v && { backgroundColor: BRAND.primary + '33', borderColor: BRAND.primary },
                                    { borderColor: isDark ? colors.borderDark : BRAND.border },
                                ]}
                                onPress={() => setForm((prev) => ({ ...prev, is_fully_immunized: v }))}
                            >
                                <Text style={{ color: textMain }}>{v === '' ? 'Unset' : v === '1' ? 'Yes' : 'No'}</Text>
                            </TouchableOpacity>
                        ))}
                    </View>
                    <Input label="Emergency contact name" value={form.emergency_contact_name} onChangeText={(v) => setForm((f) => ({ ...f, emergency_contact_name: v }))} />
                    <Input
                        label="Emergency contact phone (local digits)"
                        value={form.emergency_contact_phone}
                        onChangeText={(v) => setForm((f) => ({ ...f, emergency_contact_phone: v }))}
                        keyboardType="phone-pad"
                    />
                </Card>

                <Card style={[styles.card, { backgroundColor: surface, borderRadius: RADIUS.card }]}>
                    <Text style={[styles.sectionTitle, { color: textMain }]}>Other</Text>
                    <Input label="Religion" value={form.religion} onChangeText={(v) => setForm((f) => ({ ...f, religion: v }))} />
                    <Input label="NEMIS #" value={form.nemis_number} onChangeText={(v) => setForm((f) => ({ ...f, nemis_number: v }))} />
                    <Input label="KNEC assessment #" value={form.knec_assessment_number} onChangeText={(v) => setForm((f) => ({ ...f, knec_assessment_number: v }))} />
                    <Input label="Admission date" value={form.admission_date} onChangeText={(v) => setForm((f) => ({ ...f, admission_date: v }))} placeholder="YYYY-MM-DD" />
                </Card>

                <View style={styles.actions}>
                    <Button title="Cancel" onPress={() => navigation.goBack()} variant="outline" style={{ flex: 1 }} />
                    <Button title={isEdit ? 'Save' : 'Create'} onPress={handleSubmit} loading={loading} style={{ flex: 1 }} />
                </View>
            </ScrollView>

            <Modal visible={classModal} animationType="slide" transparent>
                <View style={styles.modalOverlay}>
                    <View style={[styles.modalBox, { backgroundColor: surface }]}>
                        <Text style={[styles.sectionTitle, { color: textMain }]}>Select class</Text>
                        <FlatList
                            data={classes}
                            keyExtractor={(i) => String(i.id)}
                            renderItem={({ item }) => (
                                <TouchableOpacity
                                    style={styles.modalItem}
                                    onPress={() => {
                                        setForm((f) => ({ ...f, classroom_id: String(item.id), stream_id: '' }));
                                        setClassModal(false);
                                    }}
                                >
                                    <Text style={{ color: textMain }}>{item.name}</Text>
                                </TouchableOpacity>
                            )}
                        />
                        <Button title="Close" variant="outline" onPress={() => setClassModal(false)} />
                    </View>
                </View>
            </Modal>

            <Modal visible={streamModal} animationType="slide" transparent>
                <View style={styles.modalOverlay}>
                    <View style={[styles.modalBox, { backgroundColor: surface }]}>
                        <Text style={[styles.sectionTitle, { color: textMain }]}>Select stream</Text>
                        <FlatList
                            data={streams}
                            keyExtractor={(i) => String(i.id)}
                            renderItem={({ item }) => (
                                <TouchableOpacity
                                    style={styles.modalItem}
                                    onPress={() => {
                                        setForm((f) => ({ ...f, stream_id: String(item.id) }));
                                        setStreamModal(false);
                                    }}
                                >
                                    <Text style={{ color: textMain }}>{item.name}</Text>
                                </TouchableOpacity>
                            )}
                        />
                        <Button title="Close" variant="outline" onPress={() => setStreamModal(false)} />
                    </View>
                </View>
            </Modal>

            <Modal visible={catModal} animationType="slide" transparent>
                <View style={styles.modalOverlay}>
                    <View style={[styles.modalBox, { backgroundColor: surface }]}>
                        <Text style={[styles.sectionTitle, { color: textMain }]}>Select category</Text>
                        <FlatList
                            data={categories}
                            keyExtractor={(i) => String(i.id)}
                            renderItem={({ item }) => (
                                <TouchableOpacity
                                    style={styles.modalItem}
                                    onPress={() => {
                                        setForm((f) => ({ ...f, category_id: String(item.id) }));
                                        setCatModal(false);
                                    }}
                                >
                                    <Text style={{ color: textMain }}>{item.name}</Text>
                                </TouchableOpacity>
                            )}
                        />
                        <Button title="Close" variant="outline" onPress={() => setCatModal(false)} />
                    </View>
                </View>
            </Modal>
            </KeyboardAvoidingView>
        </SafeAreaView>
    );
};

export const EditStudentScreen = StudentFormScreen;

const styles = StyleSheet.create({
    container: { flex: 1 },
    scroll: { flex: 1 },
    scrollContent: { padding: SPACING.lg, paddingTop: SPACING.md, paddingBottom: SPACING.xxl },
    card: { marginBottom: SPACING.lg, padding: SPACING.md },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: '700', marginBottom: SPACING.md },
    label: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.xs },
    hint: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.md },
    chips: { flexDirection: 'row', flexWrap: 'wrap', gap: SPACING.sm, marginBottom: SPACING.md },
    chip: {
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        borderRadius: 8,
        borderWidth: 1,
    },
    selectBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        borderWidth: 1,
        borderRadius: 8,
        padding: SPACING.md,
        marginBottom: SPACING.md,
    },
    uploadRow: { flexDirection: 'row', alignItems: 'center', marginBottom: SPACING.md },
    rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: SPACING.md },
    actions: { flexDirection: 'row', gap: SPACING.md, marginTop: SPACING.md },
    modalOverlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'flex-end',
    },
    modalBox: { padding: SPACING.lg, maxHeight: '70%', borderTopLeftRadius: 16, borderTopRightRadius: 16 },
    modalItem: { paddingVertical: SPACING.md, borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: BRAND.border },
});
