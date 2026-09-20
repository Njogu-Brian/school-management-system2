import {
  useCan,
  useClassrooms,
  useClassroomStreams,
  useInfiniteParentsContact,
  type ParentContactRecord,
} from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  StudentSearchBar,
  useTheme,
} from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';
import { openPhoneActions } from '../../../utils/contactActions';

type Props = StackScreenProps<StudentsStackParamList, 'ParentsContact'>;

function ContactLine({
  label,
  name,
  phone,
}: {
  label: string;
  name?: string | null;
  phone?: string | null;
}) {
  const { palette, typography, spacing, colors } = useTheme();
  if (!name && !phone) return null;
  return (
    <Pressable
      onPress={phone ? () => void openPhoneActions(phone, name ?? label) : undefined}
      style={{ flexDirection: 'row', alignItems: 'center', marginTop: spacing.xs }}
      accessibilityRole={phone ? 'button' : undefined}
    >
      <Ionicons name="call-outline" size={14} color={phone ? colors.primary : palette.textMuted} />
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: typography.caption.fontSize,
          marginLeft: 6,
          flex: 1,
        }}
      >
        {label}: {name || '—'}
        {phone ? ` · ${phone}` : ''}
      </Text>
    </Pressable>
  );
}

export const ParentsContactScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('students.view');
  const { palette, colors, spacing, typography, radius } = useTheme();
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const [streamId, setStreamId] = useState<number | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setSearch(searchInput.trim()), 400);
    return () => clearTimeout(t);
  }, [searchInput]);

  const classroomsQuery = useClassrooms({ enabled: canView });
  const streamsQuery = useClassroomStreams(classroomId, { enabled: canView });
  const listQuery = useInfiniteParentsContact(
    { search: search || undefined, classroomId, streamId },
    { enabled: canView },
  );

  const rows = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.data) ?? [],
    [listQuery.data],
  );
  const total = listQuery.data?.pages[0]?.total;

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You need students.view permission to open parent contacts."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer
      scroll
      contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
      scrollProps={{
        refreshControl: (
          <RefreshControl
            refreshing={listQuery.isRefetching && !listQuery.isFetchingNextPage}
            onRefresh={() => void listQuery.refetch()}
            colors={[colors.primary]}
          />
        ),
      }}
    >
      <AcademicScreenHeader
        title="Parent contacts"
        subtitle={total != null ? `${total} students` : 'Search a student and call home'}
        onBack={() => navigation.goBack()}
      />

      <StudentSearchBar
        value={searchInput}
        onChangeText={setSearchInput}
        placeholder="Search student, parent or phone…"
      />

      <FilterChipRow label="Class" wrap>
        <FilterChip
          label="All classes"
          active={classroomId == null}
          onPress={() => {
            setClassroomId(null);
            setStreamId(null);
          }}
        />
        {(classroomsQuery.data ?? []).map((c) => (
          <FilterChip
            key={c.id}
            label={c.name}
            active={classroomId === c.id}
            onPress={() => {
              setClassroomId(c.id);
              setStreamId(null);
            }}
          />
        ))}
      </FilterChipRow>
      {classroomId != null && (streamsQuery.data ?? []).length > 0 ? (
        <FilterChipRow label="Stream" wrap>
          <FilterChip label="All streams" active={streamId == null} onPress={() => setStreamId(null)} />
          {(streamsQuery.data ?? []).map((s) => (
            <FilterChip
              key={s.id}
              label={s.name}
              active={streamId === s.id}
              onPress={() => setStreamId(s.id)}
            />
          ))}
        </FilterChipRow>
      ) : null}

      {listQuery.isLoading ? (
        <SkeletonListRows count={6} />
      ) : listQuery.isError ? (
        <EmptyState
          title="Could not load contacts"
          message={(listQuery.error as Error).message}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void listQuery.refetch()}
        />
      ) : rows.length === 0 ? (
        <ListEmptyState
          title="No matching students"
          message="Try a name, admission number, parent name, or phone."
          icon="call-outline"
        />
      ) : (
        rows.map((row: ParentContactRecord) => (
          <View
            key={row.id}
            style={{
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.lg,
              padding: spacing.md,
              marginBottom: spacing.sm,
            }}
          >
            <Pressable
              onPress={() => navigation.navigate('StudentDetail', { studentId: row.id })}
              style={{ flexDirection: 'row', alignItems: 'center' }}
            >
              <Soft3DIcon name="person-outline" size={36} />
              <View style={{ marginLeft: spacing.sm, flex: 1 }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{row.full_name}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {row.admission_number}
                  {row.class_name ? ` · ${row.class_name}` : ''}
                  {row.stream_name ? ` ${row.stream_name}` : ''}
                </Text>
              </View>
              <Ionicons name="chevron-forward" size={16} color={palette.textMuted} />
            </Pressable>
            <ContactLine label="Father" name={row.father_name} phone={row.father_phone} />
            <ContactLine label="Mother" name={row.mother_name} phone={row.mother_phone} />
            <ContactLine label="Guardian" name={row.guardian_name} phone={row.guardian_phone} />
          </View>
        ))
      )}

      {listQuery.hasNextPage ? (
        <Pressable
          onPress={() => void listQuery.fetchNextPage()}
          style={{ alignItems: 'center', paddingVertical: spacing.md }}
        >
          {listQuery.isFetchingNextPage ? (
            <ActivityIndicator color={colors.primary} />
          ) : (
            <Text style={{ color: colors.primary, fontWeight: '700' }}>Load more</Text>
          )}
        </Pressable>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flexGrow: 1, justifyContent: 'center' },
});
