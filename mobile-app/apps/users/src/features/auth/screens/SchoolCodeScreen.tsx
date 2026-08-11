import { useSchool } from '@erp/core';
import { Button, ScreenContainer, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

/**
 * First-run / switch-school gate: enter control-plane school code → tenant API.
 */
export const SchoolCodeScreen: React.FC = () => {
  const { selectSchoolByCode, submitting, error } = useSchool();
  const { colors, spacing, typography, radius, palette } = useTheme();
  const insets = useSafeAreaInsets();
  const [code, setCode] = useState('');

  const onContinue = async () => {
    await selectSchoolByCode(code);
  };

  return (
    <ScreenContainer edges={['left', 'right']}>
      <KeyboardAvoidingView
        style={[styles.flex, { paddingTop: insets.top + spacing.xl }]}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <View style={[styles.card, { backgroundColor: palette.surface, borderRadius: radius.lg }]}>
          <Text style={[typography.title, { color: palette.textPrimary, marginBottom: spacing.sm }]}>
            Enter school code
          </Text>
          <Text style={[typography.body, { color: palette.textSecondary, marginBottom: spacing.lg }]}>
            Your school gives you a unique code. Enter it once to connect this app to your school.
          </Text>
          <TextInput
            autoCapitalize="characters"
            autoCorrect={false}
            value={code}
            onChangeText={setCode}
            placeholder="e.g. RKS001"
            placeholderTextColor={palette.textSecondary}
            style={[
              styles.input,
              {
                borderColor: palette.border,
                color: palette.textPrimary,
                borderRadius: radius.md,
                marginBottom: spacing.md,
              },
            ]}
            editable={!submitting}
            onSubmitEditing={() => void onContinue()}
            returnKeyType="go"
          />
          {error ? (
            <Text style={{ color: colors.error, marginBottom: spacing.md }}>{error}</Text>
          ) : null}
          <Button
            label={submitting ? 'Connecting…' : 'Continue'}
            onPress={() => void onContinue()}
            disabled={submitting || code.trim().length < 3}
            loading={submitting}
          />
        </View>
      </KeyboardAvoidingView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1, paddingHorizontal: 24, justifyContent: 'center' },
  card: { padding: 24 },
  input: {
    borderWidth: 1,
    paddingHorizontal: 14,
    paddingVertical: Platform.OS === 'ios' ? 14 : 10,
    fontSize: 18,
    letterSpacing: 1,
  },
});
