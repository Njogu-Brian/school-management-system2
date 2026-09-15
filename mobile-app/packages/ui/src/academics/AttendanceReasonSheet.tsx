import React, { useEffect, useState } from 'react';
import {
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { FilterChip, FilterChipRow } from '../primitives/FilterChip';
import { Button } from '../primitives/Button';
import { TextField } from '../primitives/TextField';
import { useAdaptiveLayout } from '../layout/useAdaptiveLayout';
import { useTheme } from '../theme/ThemeContext';
import type { AttendanceReasonDraft } from './attendanceReason';

export type AttendanceReasonCodeOption = {
  id: number;
  name: string;
};

export interface AttendanceReasonSheetProps {
  visible: boolean;
  studentName?: string;
  statusLabel?: string;
  codes: AttendanceReasonCodeOption[];
  value?: AttendanceReasonDraft | null;
  onSave: (value: AttendanceReasonDraft) => void;
  onSkip: () => void;
}

export const AttendanceReasonSheet: React.FC<AttendanceReasonSheetProps> = ({
  visible,
  studentName,
  statusLabel = 'absent',
  codes,
  value,
  onSave,
  onSkip,
}) => {
  const { palette, spacing, typography, radius, opacity, elevation, isDark } = useTheme();
  const { height, isTablet } = useAdaptiveLayout();
  const [codeId, setCodeId] = useState<number | null>(value?.reason_code_id ?? null);
  const [notes, setNotes] = useState(value?.excuse_notes || value?.reason || '');

  useEffect(() => {
    if (!visible) return;
    setCodeId(value?.reason_code_id ?? null);
    setNotes(value?.excuse_notes || value?.reason || '');
  }, [visible, value]);

  const dialogBg = isDark ? '#2B3444' : '#FFFFFF';
  const textMain = isDark ? '#F3F6FB' : palette.textMain;
  const textSub = isDark ? '#C5CEDC' : palette.textSub;
  const border = isDark ? 'rgba(255,255,255,0.14)' : palette.borderSubtle;

  const save = () => {
    onSave({
      reason_code_id: codeId,
      reason: notes.trim() || null,
      excuse_notes: notes.trim() || null,
    });
  };

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={onSkip}
      statusBarTranslucent
    >
      <KeyboardAvoidingView
        style={styles.root}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <Pressable
          style={[styles.scrim, { backgroundColor: `rgba(0,0,0,${Math.min(opacity.scrim, 0.5)})` }]}
          onPress={onSkip}
          accessibilityRole="button"
          accessibilityLabel="Skip reason"
        />
        <View style={styles.center} pointerEvents="box-none">
          <View
            style={[
              styles.card,
              elevation[4] ?? elevation[3],
              {
                backgroundColor: dialogBg,
                borderRadius: radius.dialog,
                padding: spacing.lg,
                borderColor: border,
                maxHeight: Math.round(height * 0.88),
                maxWidth: isTablet ? 520 : 420,
              },
            ]}
          >
            <Text
              style={{
                color: textMain,
                fontSize: typography.title.fontSize,
                fontWeight: typography.title.fontWeight,
              }}
            >
              Reason
            </Text>
            <Text
              style={{
                color: textSub,
                fontSize: typography.body.fontSize,
                marginTop: spacing.xs,
                marginBottom: spacing.md,
              }}
            >
              {studentName
                ? `Why is ${studentName} ${statusLabel}? Optional, but parents see this.`
                : 'Optional. Parents see this on the notification.'}
            </Text>

            <ScrollView
              style={styles.scroll}
              keyboardShouldPersistTaps="handled"
              nestedScrollEnabled
            >
              {codes.length > 0 ? (
                <FilterChipRow label="Preset">
                  {codes.map((code) => (
                    <FilterChip
                      key={code.id}
                      label={code.name}
                      active={codeId === code.id}
                      onPress={() => setCodeId(codeId === code.id ? null : code.id)}
                    />
                  ))}
                </FilterChipRow>
              ) : null}
              <TextField
                label="Notes"
                value={notes}
                onChangeText={setNotes}
                placeholder="e.g. doctor appointment, bus delay"
                multiline
              />
            </ScrollView>

            <View style={{ gap: spacing.sm, marginTop: spacing.md }}>
              <Button label="Save reason" onPress={save} />
              <Button label="Skip" onPress={onSkip} variant="ghost" />
            </View>
          </View>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
};

const styles = StyleSheet.create({
  root: { flex: 1 },
  scrim: { ...StyleSheet.absoluteFillObject },
  center: {
    ...StyleSheet.absoluteFillObject,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 20,
    zIndex: 2,
    elevation: 8,
  },
  card: {
    width: '100%',
    borderWidth: StyleSheet.hairlineWidth,
    zIndex: 3,
    minHeight: 0,
  },
  scroll: { flexGrow: 1, flexShrink: 1, minHeight: 0 },
});
