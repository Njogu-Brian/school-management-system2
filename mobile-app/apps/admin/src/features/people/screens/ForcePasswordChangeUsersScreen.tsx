import { usePasswordChangeTargets, useRequirePasswordChange } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { Alert, Pressable, ScrollView, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<PeopleStackParamList, 'ForcePasswordChange'>;
type Group = 'staff' | 'parents' | 'all';

export const ForcePasswordChangeUsersScreen: React.FC<Props> = ({ navigation }) => {
  const { palette, spacing, typography, radius, colors } = useTheme();
  const [group, setGroup] = useState<Group>('staff');
  const [search, setSearch] = useState('');
  const [selected, setSelected] = useState<number[]>([]);
  const query = usePasswordChangeTargets({ group, q: search.trim() || undefined });
  const mutation = useRequirePasswordChange();
  const users = query.data?.data ?? [];

  const toggle = (id: number) => {
    setSelected((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  };

  const requireSelected = () => {
    if (selected.length === 0) {
      showError('Select users', 'Tick one or more people, or require everyone in this list.');
      return;
    }
    Alert.alert('Require password change', `${selected.length} user(s) must change password on next login.`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Require',
        onPress: () =>
          void mutation
            .mutateAsync({ group, user_ids: selected })
            .then((res) => {
              showSuccess('Done', `${res?.count ?? selected.length} user(s) will change password next login.`);
              setSelected([]);
            })
            .catch((err) => showError('Failed', err instanceof Error ? err.message : 'Try again.')),
      },
    ]);
  };

  const requireAll = () => {
    Alert.alert('Everyone in this list', 'All matching staff/parents must change password on next login.', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Require all',
        onPress: () =>
          void mutation
            .mutateAsync({ group, all: true, q: search.trim() || undefined })
            .then((res) => showSuccess('Done', `${res?.count ?? 0} user(s) will change password next login.`))
            .catch((err) => showError('Failed', err instanceof Error ? err.message : 'Try again.')),
      },
    ]);
  };

  const chips = useMemo(
    () =>
      [
        { id: 'staff' as const, label: 'Staff' },
        { id: 'parents' as const, label: 'Parents' },
        { id: 'all' as const, label: 'Both' },
      ],
    [],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title="Require password change"
          subtitle="Next login on web and mobile"
          onBack={() => navigation.goBack()}
        />
        <SearchBar value={search} onChangeText={setSearch} placeholder="Name, email or phone" />
        <FilterChipRow>
          {chips.map((chip) => (
            <FilterChip
              key={chip.id}
              label={chip.label}
              active={group === chip.id}
              onPress={() => {
                setGroup(chip.id);
                setSelected([]);
              }}
            />
          ))}
        </FilterChipRow>
        <View style={{ flexDirection: 'row', gap: spacing.sm, marginVertical: spacing.md }}>
          <View style={{ flex: 1 }}>
            <Button label="Selected" onPress={requireSelected} loading={mutation.isPending} />
          </View>
          <View style={{ flex: 1 }}>
            <Button label="Everyone in list" variant="secondary" onPress={requireAll} loading={mutation.isPending} />
          </View>
        </View>
        {query.isLoading ? <SkeletonListRows variant="card" /> : null}
        {query.isError ? (
          <EmptyState title="Could not load users" message={(query.error as Error).message} icon="alert-circle-outline" />
        ) : null}
        {users.map((user) => {
          const on = selected.includes(user.id);
          return (
            <Pressable
              key={user.id}
              onPress={() => toggle(user.id)}
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                padding: spacing.md,
                marginBottom: spacing.sm,
                borderRadius: radius.md,
                borderWidth: 1,
                borderColor: on ? colors.primary : palette.border,
                backgroundColor: palette.surface,
              }}
            >
              <View
                style={{
                  width: 22,
                  height: 22,
                  borderRadius: 6,
                  borderWidth: 2,
                  borderColor: on ? colors.primary : palette.border,
                  backgroundColor: on ? colors.primary : 'transparent',
                  marginRight: spacing.sm,
                }}
              />
              <View style={{ flex: 1 }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{user.name}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {user.login ?? '—'} · {user.groups.join(', ')}
                  {user.must_change_password ? ' · pending change' : ''}
                </Text>
              </View>
            </Pressable>
          );
        })}
      </ScrollView>
    </ScreenContainer>
  );
};
