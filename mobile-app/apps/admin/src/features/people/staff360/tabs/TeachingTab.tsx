import {
  useSaveStaffTeachingAssignments,
  useStaffTeachingAssignments,
  useTeacherStreamSlots,
  type TeacherStreamSlot,
} from '@erp/core';
import { ScreenContainer, useTheme } from '@erp/ui';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  View,
} from 'react-native';

const TEACHER_ROLES = ['teacher', 'senior teacher', 'supervisor', 'academic administrator'];

function isTeachingRole(role?: string | null): boolean {
  if (!role) return false;
  return TEACHER_ROLES.includes(role.trim().toLowerCase());
}

interface SlotState {
  classroom_id: number;
  stream_id: number | null;
  is_class_teacher: boolean;
  is_assistant_teacher: boolean;
  subject_ids: number[];
}

export interface TeachingTabProps {
  staffId: number;
  systemRole?: string | null;
}

export const TeachingTab: React.FC<TeachingTabProps> = ({ staffId, systemRole }) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
  const hasTeachingRole = isTeachingRole(systemRole);

  const slotsQuery = useTeacherStreamSlots(hasTeachingRole);
  const assignmentsQuery = useStaffTeachingAssignments(staffId, hasTeachingRole);
  const saveMutation = useSaveStaffTeachingAssignments(staffId);

  const [selectedKeys, setSelectedKeys] = useState<Set<string>>(new Set());
  const [slotState, setSlotState] = useState<Record<string, SlotState>>({});

  useEffect(() => {
    const data = assignmentsQuery.data?.assignments;
    if (!data) return;
    const keys = new Set<string>();
    const state: Record<string, SlotState> = {};
    for (const slot of data.slots) {
      keys.add(slot.key);
      state[slot.key] = {
        classroom_id: slot.classroom_id,
        stream_id: slot.stream_id,
        is_class_teacher: slot.is_class_teacher,
        is_assistant_teacher: slot.is_assistant_teacher,
        subject_ids: [...slot.subject_ids],
      };
    }
    setSelectedKeys(keys);
    setSlotState(state);
  }, [assignmentsQuery.data]);

  const streamSlots = slotsQuery.data ?? [];

  const toggleStream = useCallback((slot: TeacherStreamSlot) => {
    setSelectedKeys((prev) => {
      const next = new Set(prev);
      if (next.has(slot.key)) {
        next.delete(slot.key);
        setSlotState((s) => {
          const copy = { ...s };
          delete copy[slot.key];
          return copy;
        });
      } else {
        next.add(slot.key);
        setSlotState((s) => ({
          ...s,
          [slot.key]: {
            classroom_id: slot.classroom_id,
            stream_id: slot.stream_id,
            is_class_teacher: false,
            is_assistant_teacher: false,
            subject_ids: [],
          },
        }));
      }
      return next;
    });
  }, []);

  const updateSlot = useCallback((key: string, patch: Partial<SlotState>) => {
    setSlotState((s) => ({ ...s, [key]: { ...s[key], ...patch } }));
  }, []);

  const toggleSubject = useCallback((key: string, subjectId: number) => {
    setSlotState((s) => {
      const current = s[key];
      if (!current) return s;
      const ids = new Set(current.subject_ids);
      if (ids.has(subjectId)) ids.delete(subjectId);
      else ids.add(subjectId);
      return { ...s, [key]: { ...current, subject_ids: [...ids] } };
    });
  }, []);

  const handleSave = useCallback(() => {
    const payloads = [...selectedKeys]
      .map((key) => slotState[key])
      .filter(Boolean);
    void saveMutation.mutateAsync(payloads);
  }, [selectedKeys, slotState, saveMutation]);

  const selectedSlots = useMemo(
    () => streamSlots.filter((s) => selectedKeys.has(s.key)),
    [streamSlots, selectedKeys],
  );

  if (!hasTeachingRole) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <Text
          style={{
            color: palette.textSecondary,
            textAlign: 'center',
            fontSize: typography.body.fontSize,
          }}
        >
          Teaching assignments are available when this staff member has a teaching role.
        </Text>
      </ScreenContainer>
    );
  }

  if (slotsQuery.isLoading || assignmentsQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  return (
    <ScrollView contentContainerStyle={{ padding: spacing.md, gap: spacing.md }}>
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.titleSmall.fontSize,
          fontWeight: typography.titleSmall.fontWeight,
        }}
      >
        Streams to teach
      </Text>
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: typography.body.fontSize,
          marginBottom: spacing.sm,
        }}
      >
        Select streams, then choose learning areas and homeroom roles for each.
      </Text>

      {streamSlots.map((slot) => {
        const active = selectedKeys.has(slot.key);
        return (
          <Pressable
            key={slot.key}
            onPress={() => toggleStream(slot)}
            style={[
              styles.streamPick,
              {
                borderColor: active ? colors.primary : palette.border,
                backgroundColor: active ? `${colors.primary}14` : palette.surfaceRaised,
                borderRadius: radius.control,
                padding: spacing.mdSm,
                marginBottom: spacing.sm,
              },
            ]}
          >
            <Text
              style={{
                color: palette.textPrimary,
                fontWeight: active ? '600' : '400',
                fontSize: typography.body.fontSize,
              }}
            >
              {slot.label}
            </Text>
          </Pressable>
        );
      })}

      {selectedSlots.map((slot) => {
        const state = slotState[slot.key];
        if (!state) return null;
        return (
          <View
            key={slot.key}
            style={[
              styles.card,
              {
                borderColor: palette.border,
                backgroundColor: palette.surfaceRaised,
                borderRadius: radius.card,
                padding: spacing.md,
                gap: spacing.sm,
              },
            ]}
          >
            <Text
              style={{
                color: palette.textPrimary,
                fontSize: typography.label.fontSize,
                fontWeight: '600',
                marginBottom: spacing.xs,
              }}
            >
              {slot.label}
            </Text>

            <View style={styles.roleRow}>
              <Text style={{ color: palette.textPrimary, flex: 1, fontSize: typography.body.fontSize }}>
                Class Teacher
              </Text>
              <Switch
                value={state.is_class_teacher}
                onValueChange={(v) => updateSlot(slot.key, { is_class_teacher: v })}
              />
            </View>
            <View style={styles.roleRow}>
              <Text style={{ color: palette.textPrimary, flex: 1, fontSize: typography.body.fontSize }}>
                Assistant Teacher
              </Text>
              <Switch
                value={state.is_assistant_teacher}
                onValueChange={(v) => updateSlot(slot.key, { is_assistant_teacher: v })}
              />
            </View>

            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                marginTop: spacing.xs,
                marginBottom: spacing.xs,
              }}
            >
              Learning areas
            </Text>
            {slot.subjects.length === 0 ? (
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                No subjects configured for this stream.
              </Text>
            ) : (
              slot.subjects.map((sub) => {
                const checked = state.subject_ids.includes(sub.subject_id);
                return (
                  <Pressable
                    key={sub.subject_id}
                    onPress={() => toggleSubject(slot.key, sub.subject_id)}
                    style={[
                      styles.subjectChip,
                      {
                        borderColor: checked ? colors.primary : palette.border,
                        backgroundColor: checked ? `${colors.primary}18` : palette.background,
                        borderRadius: radius.control,
                        paddingVertical: spacing.sm,
                        paddingHorizontal: spacing.mdSm,
                        marginBottom: spacing.xs,
                      },
                    ]}
                  >
                    <Text style={{ color: palette.textPrimary, fontSize: typography.body.fontSize }}>
                      {sub.name}
                    </Text>
                  </Pressable>
                );
              })
            )}
          </View>
        );
      })}

      <Pressable
        onPress={handleSave}
        disabled={saveMutation.isPending}
        style={[
          styles.saveBtn,
          {
            backgroundColor: colors.primary,
            borderRadius: radius.control,
            paddingVertical: spacing.md,
            marginTop: spacing.sm,
          },
        ]}
      >
        {saveMutation.isPending ? (
          <ActivityIndicator color={colors.white} />
        ) : (
          <Text
            style={{
              color: colors.white,
              fontWeight: '600',
              fontSize: typography.button.fontSize,
            }}
          >
            Save teaching assignments
          </Text>
        )}
      </Pressable>

      {saveMutation.isSuccess ? (
        <Text
          style={{
            color: colors.success,
            textAlign: 'center',
            fontSize: typography.body.fontSize,
          }}
        >
          Saved successfully.
        </Text>
      ) : null}
      {saveMutation.isError ? (
        <Text
          style={{
            color: colors.error,
            textAlign: 'center',
            fontSize: typography.body.fontSize,
          }}
        >
          Could not save assignments.
        </Text>
      ) : null}
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
  streamPick: {
    borderWidth: 1,
  },
  card: {
    borderWidth: 1,
  },
  roleRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  subjectChip: {
    borderWidth: 1,
  },
  saveBtn: {
    alignItems: 'center',
  },
});
