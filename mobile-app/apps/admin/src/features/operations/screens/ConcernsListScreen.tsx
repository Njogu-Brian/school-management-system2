import { useConcernsList } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  ListEmptyState,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, FlatList, Pressable, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'ConcernsList'>;

export const ConcernsListScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
  const query = useConcernsList();

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ padding: spacing.md, flex: 1 }}>
        <AcademicScreenHeader
          title="Concerns"
          subtitle="Parent issues & staff follow-up"
          onBack={() => navigation.goBack()}
        />
        <Button
          label="Raise concern"
          onPress={() => navigation.navigate('ConcernCreate')}
          style={{ marginBottom: spacing.md }}
        />
        {query.isLoading ? <ActivityIndicator color={colors.primary} /> : null}
        <FlatList
          data={query.data ?? []}
          keyExtractor={(item) => String(item.id)}
          renderItem={({ item }) => (
            <Pressable
              onPress={() => navigation.navigate('ConcernDetail', { concernId: item.id })}
              style={[
                styles.row,
                {
                  borderColor: palette.borderSubtle,
                  backgroundColor: palette.surfaceRaised,
                  borderRadius: radius.card,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                },
              ]}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                {item.student_name ?? `Student #${item.student_id}`}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                {item.category} · {item.status}
                {item.class_name ? ` · ${item.class_name}` : ''}
              </Text>
            </Pressable>
          )}
          ListEmptyComponent={
            !query.isLoading ? <ListEmptyState entityName="concerns" icon="alert-circle-outline" /> : null
          }
        />
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { borderWidth: StyleSheet.hairlineWidth },
});
