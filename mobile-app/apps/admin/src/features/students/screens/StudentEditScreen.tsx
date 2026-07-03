import {
  studentsApi,
  useClassrooms,
  useStudentCategories,
  useUpdateStudent,
} from '@erp/core';
import { AcademicScreenHeader, Button, FilterChip, FilterChipRow, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, ScrollView } from 'react-native';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';

type Props = StackScreenProps<StudentsStackParamList, 'StudentEdit'>;

export const StudentEditScreen: React.FC<Props> = ({ route, navigation }) => {
  const { studentId } = route.params;
  const { colors, spacing } = useTheme();
  const updateMutation = useUpdateStudent(studentId);
  const classroomsQuery = useClassrooms();
  const categoriesQuery = useStudentCategories();

  const [loading, setLoading] = useState(true);
  const [firstName, setFirstName] = useState('');
  const [middleName, setMiddleName] = useState('');
  const [lastName, setLastName] = useState('');
  const [gender, setGender] = useState('male');
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [residentialArea, setResidentialArea] = useState('');
  const [fatherPhone, setFatherPhone] = useState('');
  const [motherPhone, setMotherPhone] = useState('');
  const [admissionDate, setAdmissionDate] = useState('');

  useEffect(() => {
    void studentsApi.getById(studentId).then((res) => {
      const s = res.data;
      if (!s) return;
      setFirstName(s.first_name);
      setMiddleName(s.middle_name ?? '');
      setLastName(s.last_name);
      setGender(s.gender ?? 'male');
      setClassroomId(s.classroom_id ?? s.class_id ?? null);
      setCategoryId(s.category_id ?? null);
      setResidentialArea(s.residential_area ?? s.address ?? '');
      setFatherPhone(s.parent?.father_phone ?? '');
      setMotherPhone(s.parent?.mother_phone ?? '');
      setAdmissionDate((s.admission_date ?? s.created_at ?? '').slice(0, 10));
      setLoading(false);
    });
  }, [studentId]);

  const save = async () => {
    if (!classroomId || !categoryId) {
      Alert.alert('Missing fields', 'Class and category are required.');
      return;
    }
    try {
      await updateMutation.mutateAsync({
        first_name: firstName.trim(),
        middle_name: middleName.trim() || null,
        last_name: lastName.trim(),
        gender,
        classroom_id: classroomId,
        category_id: categoryId,
        residential_area: residentialArea.trim(),
        admission_date: admissionDate,
        father_phone: fatherPhone.trim() || motherPhone.trim(),
        mother_phone: motherPhone.trim() || null,
        father_name: firstName.trim(),
      });
      Alert.alert('Saved', 'Student profile updated.');
      navigation.goBack();
    } catch (err) {
      Alert.alert('Error', err instanceof Error ? err.message : 'Update failed.');
    }
  };

  if (loading) {
    return (
      <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Edit student" onBack={() => navigation.goBack()} />
        <TextField label="First name" value={firstName} onChangeText={setFirstName} />
        <TextField label="Middle name" value={middleName} onChangeText={setMiddleName} />
        <TextField label="Last name" value={lastName} onChangeText={setLastName} />
        <FilterChipRow label="Gender">
          {(['male', 'female'] as const).map((g) => (
            <FilterChip key={g} label={g} active={gender === g} onPress={() => setGender(g)} />
          ))}
        </FilterChipRow>
        <FilterChipRow label="Class">
          {(classroomsQuery.data ?? []).map((c) => (
            <FilterChip key={c.id} label={c.name} active={classroomId === c.id} onPress={() => setClassroomId(c.id)} />
          ))}
        </FilterChipRow>
        <FilterChipRow label="Category">
          {(categoriesQuery.data ?? []).map((c) => (
            <FilterChip key={c.id} label={c.name} active={categoryId === c.id} onPress={() => setCategoryId(c.id)} />
          ))}
        </FilterChipRow>
        <TextField label="Residential area" value={residentialArea} onChangeText={setResidentialArea} />
        <TextField label="Parent phone" value={fatherPhone || motherPhone} onChangeText={setFatherPhone} keyboardType="phone-pad" />
        <TextField label="Admission date (YYYY-MM-DD)" value={admissionDate} onChangeText={setAdmissionDate} />
        <Button label="Save changes" onPress={() => void save()} loading={updateMutation.isPending} style={{ marginTop: spacing.md }} />
      </ScrollView>
    </ScreenContainer>
  );
};
