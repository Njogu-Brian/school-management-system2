import { useCan, useInfiniteLibraryBooks, useInfiniteStudentList, useIssueBook } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterBottomSheet,
  FinanceFieldSection,
  ScreenContainer,
  SearchBar,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'IssueBook'>;

interface PickedStudent {
  id: number;
  name: string;
  admission: string;
}

interface PickedBook {
  id: number;
  title: string;
  available: number;
}

export const IssueBookScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { palette, spacing, typography } = useTheme();
  const issueMutation = useIssueBook();

  const [student, setStudent] = useState<PickedStudent | null>(null);
  const [book, setBook] = useState<PickedBook | null>(null);
  const [days, setDays] = useState('14');

  const [studentPickerOpen, setStudentPickerOpen] = useState(false);
  const [studentSearch, setStudentSearch] = useState('');
  const studentsQuery = useInfiniteStudentList(
    { search: studentSearch.trim() || undefined, perPage: 25 },
    { enabled: studentPickerOpen },
  );
  const studentRows = useMemo(
    () => studentsQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [studentsQuery.data],
  );

  const [bookPickerOpen, setBookPickerOpen] = useState(false);
  const [bookSearch, setBookSearch] = useState('');
  const booksQuery = useInfiniteLibraryBooks({
    enabled: bookPickerOpen,
    search: bookSearch.trim() || undefined,
  });
  const bookRows = useMemo(() => booksQuery.data?.pages.flatMap((p) => p.items) ?? [], [booksQuery.data]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const parsedDays = Number(days);
  const daysValid = Number.isInteger(parsedDays) && parsedDays >= 1 && parsedDays <= 90;
  const canSubmit = student != null && book != null && daysValid;

  const onIssue = async () => {
    if (!canSubmit || !student || !book) {
      showError('Validation', 'Pick a student, a book and a valid loan period (1–90 days).');
      return;
    }
    try {
      await issueMutation.mutateAsync({
        student_id: student.id,
        book_id: book.id,
        days: parsedDays,
      });
      showSuccess('Issued', `"${book.title}" issued to ${student.name}.`, () => navigation.goBack());
    } catch (err) {
      showError('Issue failed', (err as Error).message);
    }
  };

  const pickerRowStyle = ({ pressed }: { pressed: boolean }) => ({
    paddingVertical: spacing.sm,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: palette.borderSubtle,
    opacity: pressed ? 0.7 : 1,
  });

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Issue a book"
        subtitle="Lend a catalogue copy to a student"
        onBack={() => navigation.goBack()}
      />

      <FinanceFieldSection
        title="Selection"
        rows={[
          { label: 'Student', value: student ? `${student.name} (${student.admission})` : 'Not selected' },
          {
            label: 'Book',
            value: book ? `${book.title} · ${book.available} available` : 'Not selected',
          },
        ]}
      />

      <View style={{ gap: spacing.sm, marginTop: spacing.sm }}>
        <Button label={student ? 'Change student' : 'Pick student'} variant="secondary" onPress={() => setStudentPickerOpen(true)} />
        <Button label={book ? 'Change book' : 'Pick book'} variant="secondary" onPress={() => setBookPickerOpen(true)} />
      </View>

      <TextField label="Loan period (days)" value={days} onChangeText={setDays} placeholder="14" keyboardType="numeric" />

      <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
        A library card is created automatically if the student doesn't have an active one.
        Cards allow up to 3 books at a time.
      </Text>

      <View style={{ marginTop: spacing.lg }}>
        <Button
          label={issueMutation.isPending ? 'Issuing…' : 'Issue book'}
          onPress={() => void onIssue()}
          disabled={!canSubmit || issueMutation.isPending}
          loading={issueMutation.isPending}
        />
      </View>

      <FilterBottomSheet
        visible={studentPickerOpen}
        onClose={() => setStudentPickerOpen(false)}
        title="Pick student"
        onApply={() => setStudentPickerOpen(false)}
        onClear={() => setStudentSearch('')}
      >
        <SearchBar value={studentSearch} onChangeText={setStudentSearch} placeholder="Search name or admission no…" />
        <ScrollView style={{ maxHeight: 360, marginTop: spacing.sm }}>
          {studentRows.map((s) => (
            <Pressable
              key={s.id}
              onPress={() => {
                setStudent({ id: s.id, name: s.fullName, admission: s.admissionNumber });
                setStudentPickerOpen(false);
              }}
              style={pickerRowStyle}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{s.fullName}</Text>
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                {[s.admissionNumber, s.className].filter(Boolean).join(' · ')}
              </Text>
            </Pressable>
          ))}
          {studentsQuery.isLoading ? (
            <Text style={{ color: palette.textMuted, paddingVertical: spacing.sm }}>Loading…</Text>
          ) : null}
          {!studentsQuery.isLoading && studentRows.length === 0 ? (
            <Text style={{ color: palette.textMuted, paddingVertical: spacing.sm }}>No matching students.</Text>
          ) : null}
        </ScrollView>
      </FilterBottomSheet>

      <FilterBottomSheet
        visible={bookPickerOpen}
        onClose={() => setBookPickerOpen(false)}
        title="Pick book"
        onApply={() => setBookPickerOpen(false)}
        onClear={() => setBookSearch('')}
      >
        <SearchBar value={bookSearch} onChangeText={setBookSearch} placeholder="Search title, author or ISBN…" />
        <ScrollView style={{ maxHeight: 360, marginTop: spacing.sm }}>
          {bookRows.map((b) => {
            const available = b.available_copies ?? 0;
            return (
              <Pressable
                key={b.id}
                disabled={available <= 0}
                onPress={() => {
                  setBook({ id: b.id, title: b.title, available });
                  setBookPickerOpen(false);
                }}
                style={({ pressed }) => ({
                  ...pickerRowStyle({ pressed }),
                  opacity: available <= 0 ? 0.45 : pressed ? 0.7 : 1,
                })}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{b.title}</Text>
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  {[b.author, available > 0 ? `${available} available` : 'No copies available']
                    .filter(Boolean)
                    .join(' · ')}
                </Text>
              </Pressable>
            );
          })}
          {booksQuery.isLoading ? (
            <Text style={{ color: palette.textMuted, paddingVertical: spacing.sm }}>Loading…</Text>
          ) : null}
          {!booksQuery.isLoading && bookRows.length === 0 ? (
            <Text style={{ color: palette.textMuted, paddingVertical: spacing.sm }}>No matching books.</Text>
          ) : null}
        </ScrollView>
      </FilterBottomSheet>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
