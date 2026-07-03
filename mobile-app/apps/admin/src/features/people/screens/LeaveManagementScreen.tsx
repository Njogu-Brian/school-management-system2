import { useApprovalActions, useApprovalList } from '@erp/core';
import { AcademicScreenHeader, ListEmptyState, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, Alert, FlatList, Pressable, StyleSheet, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'LeaveManagement'>;

export const LeaveManagementScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const listQuery = useApprovalList({
    filters: { status: 'pending', sourceType: 'leave_request', priority: 'all' },
    includeAdmissions: false,
  });
  const actions = useApprovalActions();

  const items = (listQuery.data ?? []).filter((i) => i.sourceType === 'leave_request');

  const approve = (item: (typeof items)[number]) => {
    Alert.alert('Approve leave', 'Approve this leave request?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Approve',
        onPress: () =>
          void actions.approve
            .mutateAsync({ id: item.id })
            .then(() => Alert.alert('Approved', 'Leave request approved.'))
            .catch((e) => Alert.alert('Error', (e as Error).message)),
      },
    ]);
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ padding: spacing.md, flex: 1 }}>
        <AcademicScreenHeader
          title="Leave approvals"
          subtitle="Pending leave requests"
          onBack={() => navigation.goBack()}
        />
        {listQuery.isLoading ? <ActivityIndicator color={colors.primary} /> : null}
        <FlatList
          data={items}
          keyExtractor={(item) => item.id}
          contentContainerStyle={{ paddingBottom: spacing.xl }}
          renderItem={({ item }) => (
            <Pressable
              onPress={() => approve(item)}
              style={[styles.row, { borderColor: palette.border, backgroundColor: palette.surfaceRaised }]}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.title}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>{item.subtitle}</Text>
            </Pressable>
          )}
          ListEmptyComponent={
            !listQuery.isLoading ? <ListEmptyState entityName="pending leave requests" icon="calendar-outline" /> : null
          }
        />
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 10, padding: 12, marginBottom: 8 },
});
