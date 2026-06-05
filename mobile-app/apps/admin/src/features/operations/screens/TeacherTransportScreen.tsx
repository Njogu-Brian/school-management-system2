import { useTeacherTransportActions, useTeacherTransportStudents } from '@erp/core';
import { AcademicScreenHeader, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, Alert, FlatList, Pressable, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'TeacherTransport'>;

export const TeacherTransportScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const query = useTeacherTransportStudents();
  const { markPickup, cancelPickup } = useTeacherTransportActions();

  const onMarkPickup = (studentId: number, name: string) => {
    Alert.alert('Mark parent pickup', `Record parent collection for ${name}?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Confirm',
        onPress: () =>
          void markPickup.mutateAsync({ student_id: studentId, picked_up_by: 'Parent' }).then(() => {
            Alert.alert('Recorded', 'Pickup logged.');
          }).catch((e) => Alert.alert('Failed', (e as Error).message)),
      },
    ]);
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={query.data?.students ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <AcademicScreenHeader title="Teacher transport" subtitle="GET /teacher/transport/students" onBack={() => navigation.goBack()} />
        }
        renderItem={({ item }) => (
          <View style={[styles.row, { borderColor: palette.border, padding: spacing.sm, marginBottom: spacing.xs }]}>
            <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.full_name}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
              {[item.class_name, item.morning?.trip_name, item.fee_status].filter(Boolean).join(' · ')}
            </Text>
            {item.pickup ? (
              <Pressable onPress={() => void cancelPickup.mutateAsync(item.pickup!.id)} style={{ marginTop: 6 }}>
                <Text style={{ color: colors.error, fontSize: fontSizes.xs }}>Cancel pickup</Text>
              </Pressable>
            ) : (
              <Pressable onPress={() => onMarkPickup(item.id, item.full_name)} style={{ marginTop: 6 }}>
                <Text style={{ color: colors.primary, fontSize: fontSizes.xs }}>Mark parent pickup</Text>
              </Pressable>
            )}
          </View>
        )}
        ListEmptyComponent={query.isLoading ? <ActivityIndicator color={colors.primary} /> : <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>No students on transport today.</Text>}
        refreshing={query.isRefetching}
        onRefresh={() => void query.refetch()}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({ row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8 } });
