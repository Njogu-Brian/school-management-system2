import { useAcademicContext, useExams } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { goBackInStack } from '../../../navigation/navigateToTab';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const MarksHubScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const [yearId, setYearId] = useState<number | null>(null);
  const [termId, setTermId] = useState<number | null>(null);

  const academicQuery = useAcademicContext(yearId ?? undefined);
  const years = academicQuery.data?.academic_years ?? [];
  const terms = academicQuery.data?.terms ?? [];

  useEffect(() => {
    if (yearId != null) return;
    const active = academicQuery.data?.active_academic_year_id;
    if (active) setYearId(active);
  }, [academicQuery.data?.active_academic_year_id, yearId]);

  useEffect(() => {
    if (termId != null) return;
    const current = academicQuery.data?.current_term_id;
    if (current) setTermId(current);
  }, [academicQuery.data?.current_term_id, termId]);

  useEffect(() => {
    if (termId == null || terms.length === 0) return;
    if (!terms.some((t) => t.id === termId)) {
      setTermId(null);
    }
  }, [terms, termId]);

  const examsQuery = useExams(
    {
      per_page: 30,
      for_mark_entry: true,
      academic_year_id: yearId ?? undefined,
      term_id: termId ?? undefined,
    },
    { enabled: yearId != null && termId != null },
  );

  const exams = useMemo(
    () => examsQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [examsQuery.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={exams}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl, flexGrow: 1 }}
        ListHeaderComponent={
          <View style={{ marginBottom: spacing.md }}>
            <AcademicScreenHeader
              title="Marks entry"
              subtitle="Pick year and term, then bulk or single exam — status follows open marking"
              onBack={() => goBackInStack(navigation, 'HomeMain')}
            />

            <FilterChipRow label="Academic year" wrap>
              {years.map((y) => (
                <FilterChip
                  key={y.id}
                  label={`${y.label ?? y.year ?? y.id}${y.is_active ? ' (active)' : ''}`}
                  active={yearId === y.id}
                  onPress={() => {
                    setYearId(y.id);
                    setTermId(null);
                  }}
                />
              ))}
            </FilterChipRow>
            {academicQuery.isError ? (
              <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm, fontSize: typography.caption.fontSize }}>
                Could not load academic year/term.
              </Text>
            ) : null}
            <FilterChipRow label="Term" wrap>
              {terms.map((t) => (
                <FilterChip
                  key={t.id}
                  label={`${t.name}${t.is_current ? ' (current)' : ''}`}
                  active={termId === t.id}
                  onPress={() => setTermId(t.id)}
                />
              ))}
            </FilterChipRow>

            <Pressable
              onPress={() => navigation.navigate('MarksMatrixSetup')}
              style={[
                styles.tile,
                {
                  backgroundColor: palette.surface,
                  borderColor: palette.border,
                  borderRadius: radius.lg,
                  padding: spacing.md,
                  marginBottom: spacing.md,
                  marginTop: spacing.sm,
                },
              ]}
            >
              <Soft3DIcon name="grid-outline" tone="indigo" size={44} />
              <View style={{ flex: 1, marginLeft: spacing.sm }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>Bulk marks (class × subjects)</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  Choose year, term, exam type and subjects — then enter marks
                </Text>
              </View>
            </Pressable>
            <Text
              style={{
                color: palette.textPrimary,
                fontWeight: '700',
                marginBottom: spacing.sm,
                fontSize: typography.body.fontSize,
              }}
            >
              Single exam (open for marking)
            </Text>
          </View>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() =>
              navigation.navigate('MarksExamSetup', {
                examId: item.id,
                examName: item.name,
              })
            }
            style={[
              styles.row,
              {
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderRadius: radius.lg,
                padding: spacing.md,
                marginBottom: spacing.sm,
              },
            ]}
          >
            <Soft3DIcon name="create-outline" tone="emerald" size={40} />
            <View style={{ flex: 1, marginLeft: spacing.sm }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.name}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                {[item.classroomName, item.subjectName, item.status].filter(Boolean).join(' · ') || 'Tap to enter marks'}
              </Text>
            </View>
          </Pressable>
        )}
        refreshControl={
          <RefreshControl
            refreshing={examsQuery.isRefetching && !examsQuery.isFetchingNextPage}
            onRefresh={() => void examsQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        onEndReached={() => {
          if (examsQuery.hasNextPage && !examsQuery.isFetchingNextPage) void examsQuery.fetchNextPage();
        }}
        ListEmptyComponent={
          yearId == null || termId == null || academicQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={4} />
          ) : examsQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={4} />
          ) : examsQuery.isError ? (
            <EmptyState
              title="Could not load exams"
              message={(examsQuery.error as Error)?.message ?? 'Something went wrong.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void examsQuery.refetch()}
            />
          ) : (
            <EmptyState
              title="No exams open for marks"
              message="No exams in an enterable status for this year and term. Use bulk entry, or wait for exams to open for marking."
              icon="create-outline"
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  tile: { flexDirection: 'row', alignItems: 'center', borderWidth: StyleSheet.hairlineWidth },
  row: { flexDirection: 'row', alignItems: 'center', borderWidth: StyleSheet.hairlineWidth },
});
