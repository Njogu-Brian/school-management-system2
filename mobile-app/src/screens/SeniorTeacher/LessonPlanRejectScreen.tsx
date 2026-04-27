import React, { useState } from 'react';
import { Alert, SafeAreaView, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useTheme } from '@contexts/ThemeContext';
import { academicsApi } from '@api/academics.api';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';

interface Props {
    navigation: any;
    route: { params: { planId: number } };
}

export const LessonPlanRejectScreen: React.FC<Props> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const planId = route.params?.planId;
    const [notes, setNotes] = useState('');
    const [saving, setSaving] = useState(false);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;
    const surface = isDark ? colors.surfaceDark : BRAND.surface;
    const border = isDark ? colors.borderDark : BRAND.border;

    const reject = async () => {
        if (!notes.trim()) {
            Alert.alert('Reject', 'Rejection notes are required.');
            return;
        }
        setSaving(true);
        try {
            await academicsApi.rejectLessonPlan(planId, notes.trim());
            navigation.goBack();
        } catch (e: any) {
            Alert.alert('Reject', e?.message || 'Could not reject lesson plan.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
            <View style={styles.top}>
                <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12} style={styles.back}>
                    <Icon name="arrow-back" size={24} color={colors.primary} />
                </TouchableOpacity>
                <Text style={[styles.topTitle, { color: textMain }]}>Reject</Text>
                <View style={{ width: 40 }} />
            </View>

            <ScrollView contentContainerStyle={styles.scroll}>
                <Text style={[styles.label, { color: textMain }]}>Rejection notes</Text>
                <Text style={[styles.hint, { color: textSub }]}>Explain what needs to be fixed so the teacher can resubmit.</Text>
                <TextInput
                    value={notes}
                    onChangeText={setNotes}
                    multiline
                    placeholder="e.g. Add clear objectives and include assessment method."
                    placeholderTextColor={textSub}
                    style={[styles.textarea, { backgroundColor: surface, borderColor: border, color: textMain }]}
                />

                <TouchableOpacity onPress={reject} disabled={saving} style={[styles.btn, { backgroundColor: colors.danger }]}>
                    <Text style={styles.btnText}>{saving ? 'Rejecting…' : 'Reject lesson plan'}</Text>
                </TouchableOpacity>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    top: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm },
    back: { padding: SPACING.sm },
    topTitle: { fontSize: FONT_SIZES.lg, fontWeight: '800' },
    scroll: { padding: SPACING.xl, paddingBottom: SPACING.xxl },
    label: { fontSize: FONT_SIZES.sm, fontWeight: '800', marginBottom: SPACING.xs },
    hint: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.sm },
    textarea: { borderWidth: 1, borderRadius: 12, paddingHorizontal: 12, paddingVertical: 10, minHeight: 140, fontSize: FONT_SIZES.sm, textAlignVertical: 'top' },
    btn: { marginTop: SPACING.xl, paddingVertical: 14, borderRadius: 14, alignItems: 'center' },
    btnText: { color: '#fff', fontWeight: '900', fontSize: FONT_SIZES.md },
});

