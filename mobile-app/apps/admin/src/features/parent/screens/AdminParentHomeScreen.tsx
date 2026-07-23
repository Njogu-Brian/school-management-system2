import { useCurrentUser, useParentProfileReview } from '@erp/core';
import { ScreenContainer, Soft3DIcon, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { AppModeSwitch } from '../../shared/components/AppModeSwitch';

/**
 * Admin "Home" mode — a thin parent shell for admins who also hold a parent record.
 * Lists the admin's children via the existing parent profile-review endpoint (accessible
 * students). Work mode is the full admin app; the switcher flips between them.
 */
export const AdminParentHomeScreen: React.FC = () => {
  const { palette, colors, spacing, typography, radius } = useTheme();
  const insets = useSafeAreaInsets();
  const user = useCurrentUser();
  const query = useParentProfileReview();

  const students = query.data?.students ?? [];

  return (
    <ScreenContainer
      scroll
      edges={['top', 'bottom']}
      contentContainerStyle={{ padding: spacing.md, paddingBottom: insets.bottom + spacing.xl }}
    >
      <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: spacing.md }}>
        <View style={{ flex: 1 }}>
          <Text style={{ color: palette.textPrimary, fontSize: typography.headlineLarge.fontSize, fontWeight: '800' }}>
            Home
          </Text>
          <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>
            {user?.name ? `Hi ${user.name.split(' ')[0]}` : 'Your family'}
          </Text>
        </View>
        <Pressable onPress={() => void query.refetch()} hitSlop={8} disabled={query.isFetching}>
          <Ionicons name="refresh" size={22} color={query.isFetching ? palette.textMuted : colors.primary} />
        </Pressable>
      </View>

      <AppModeSwitch style={{ marginBottom: spacing.lg }} />

      <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
        My children
      </Text>

      {query.isLoading ? (
        <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
          <ActivityIndicator color={colors.primary} />
        </View>
      ) : query.isError ? (
        <Text style={{ color: colors.error }}>
          {(query.error as Error)?.message ?? 'Could not load your children.'}
        </Text>
      ) : students.length === 0 ? (
        <Text style={{ color: palette.textSecondary }}>No children are linked to your account yet.</Text>
      ) : (
        students.map((s) => (
          <View
            key={s.id}
            style={{
              flexDirection: 'row',
              alignItems: 'center',
              gap: spacing.md,
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.lg,
              padding: spacing.md,
              marginBottom: spacing.sm,
            }}
          >
            <Soft3DIcon name="person-outline" tone="indigo" size={40} />
            <View style={{ flex: 1 }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                {[s.first_name, s.middle_name, s.last_name].filter(Boolean).join(' ') || 'Student'}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                {[s.class_name, s.admission_number].filter(Boolean).join(' · ') || 'Enrolled'}
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={palette.textMuted} />
          </View>
        ))
      )}
    </ScreenContainer>
  );
};
