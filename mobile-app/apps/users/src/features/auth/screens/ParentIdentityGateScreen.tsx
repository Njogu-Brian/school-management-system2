import { errorMessage, useAppMode, useAuth, useParentIdentityGate, useUpdateParentIdentityGate } from '@erp/core';
import { Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';
import { AppModeSwitch } from '../../shared/components/AppModeSwitch';

/**
 * Blocks the parent app until this signed-in parent (father or mother, by login phone)
 * has a real name and phone, and each child has a name and date of birth.
 */
export const ParentIdentityGateScreen: React.FC = () => {
  const { palette, colors, spacing, typography } = useTheme();
  const { refreshUser } = useAuth();
  const { canSwitch, setMode } = useAppMode();
  const query = useParentIdentityGate();
  const save = useUpdateParentIdentityGate();

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [children, setChildren] = useState<Array<{ id: number; first_name: string; last_name: string; dob: string }>>(
    [],
  );

  useEffect(() => {
    if (query.data && !query.data.required) {
      void refreshUser();
    }
  }, [query.data, refreshUser]);

  useEffect(() => {
    if (!query.data) return;
    setName(query.data.parent.name ?? '');
    setPhone(query.data.parent.phone ?? '');
    setChildren(
      query.data.children
        .filter((c) => c.required)
        .map((c) => ({
          id: c.id,
          first_name: c.first_name ?? '',
          last_name: c.last_name ?? '',
          dob: c.dob ?? '',
        })),
    );
  }, [query.data]);

  const submit = async () => {
    if (!name.trim() || name.trim().length < 2) {
      showError('Your name', 'Enter your full name.');
      return;
    }
    if (!phone.trim()) {
      showError('Your phone', 'Enter your phone number.');
      return;
    }
    for (const child of children) {
      if (!child.first_name.trim() || !child.last_name.trim()) {
        showError('Child name', 'Enter each child’s first and last name.');
        return;
      }
      if (!child.dob.trim()) {
        showError('Date of birth', 'Enter each child’s date of birth (YYYY-MM-DD).');
        return;
      }
    }

    try {
      const data = await save.mutateAsync({
        name: name.trim(),
        phone: phone.trim(),
        children: children.map((c) => ({
          id: c.id,
          first_name: c.first_name.trim(),
          last_name: c.last_name.trim(),
          dob: c.dob.trim(),
        })),
      });
      if (data.user) {
        // Keep session user in sync so the gate can close immediately.
        await refreshUser();
      } else {
        await refreshUser();
      }
      if (data.gate.required) {
        showError('Still needed', 'Please complete the remaining details.');
        return;
      }
      showSuccess('Saved', 'Thank you. Your details are up to date.');
    } catch (err) {
      showError('Could not save', errorMessage(err, 'Try again.'));
    }
  };

  if (query.isLoading) {
    return (
      <ScreenContainer>
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
          <ActivityIndicator color={colors.primary} />
        </View>
      </ScreenContainer>
    );
  }

  if (query.isError) {
    return (
      <ScreenContainer>
        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
          We need a few details
        </Text>
        <Text style={{ color: palette.textSecondary, marginBottom: spacing.md }}>
          {errorMessage(query.error, 'Could not load your profile.')}
        </Text>
        <Button label="Try again" onPress={() => void query.refetch()} />
      </ScreenContainer>
    );
  }

  const slotLabel = query.data?.slot_label ?? 'Parent';

  return (
    <ScreenContainer scroll>
      <AppModeSwitch variant="banner" style={{ marginBottom: spacing.md }} />
      {canSwitch ? (
        <Pressable
          onPress={() => void setMode('work')}
          style={{ marginBottom: spacing.md, alignSelf: 'flex-start' }}
        >
          <Text style={{ color: colors.primary, fontWeight: '700' }}>Continue as staff</Text>
        </Pressable>
      ) : null}
      <Text style={{ color: palette.textPrimary, fontSize: typography.title.fontSize, fontWeight: '700' }}>
        Complete your details
      </Text>
        <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, marginBottom: spacing.lg }}>
          Signed in as {slotLabel}. Only your details are needed — the other parent is not asked to fill this.
        </Text>

        <TextField label="Your name" value={name} onChangeText={setName} autoCapitalize="words" />
        <TextField
          label="Your phone number"
          value={phone}
          onChangeText={setPhone}
          keyboardType="phone-pad"
          placeholder="0712345678"
        />

        {children.map((child, index) => (
          <View key={child.id} style={{ marginTop: spacing.md }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.xs }}>
              Child {index + 1}
            </Text>
            <TextField
              label="First name"
              value={child.first_name}
              onChangeText={(v) =>
                setChildren((prev) => prev.map((c) => (c.id === child.id ? { ...c, first_name: v } : c)))
              }
              autoCapitalize="words"
            />
            <TextField
              label="Last name"
              value={child.last_name}
              onChangeText={(v) =>
                setChildren((prev) => prev.map((c) => (c.id === child.id ? { ...c, last_name: v } : c)))
              }
              autoCapitalize="words"
            />
            <TextField
              label="Date of birth (YYYY-MM-DD)"
              value={child.dob}
              onChangeText={(v) =>
                setChildren((prev) => prev.map((c) => (c.id === child.id ? { ...c, dob: v } : c)))
              }
              placeholder="2018-03-21"
            />
          </View>
        ))}

        <Button
          label={save.isPending ? 'Saving…' : 'Save and continue'}
          onPress={() => void submit()}
          loading={save.isPending}
          style={{ marginTop: spacing.lg }}
        />
    </ScreenContainer>
  );
};
