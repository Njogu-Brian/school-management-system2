import { isCombinedApp, PRODUCT, PRODUCT_WEBSITE_URL, useSchool } from '@erp/core';
import { Button, ChangeSchoolLink, EdulynkMark, ScreenContainer, useAdaptiveLayout, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import {
  KeyboardAvoidingView,
  Linking,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

/**
 * First-run / switch-school gate: enter control-plane school code → tenant API.
 * Combined Edulynk binary shows product identity until the school brands the app.
 */
export const SchoolCodeScreen: React.FC = () => {
  const { selectSchoolByCode, submitting, error } = useSchool();
  const { colors, spacing, typography, radius, palette } = useTheme();
  const insets = useSafeAreaInsets();
  const { isTablet, formMaxWidth } = useAdaptiveLayout();
  const [code, setCode] = useState('');
  const product = isCombinedApp();

  const onContinue = async () => {
    await selectSchoolByCode(code);
  };

  return (
    <ScreenContainer edges={['left', 'right']}>
      <KeyboardAvoidingView
        style={[styles.flex, { paddingTop: insets.top + spacing.xl }]}
        behavior="padding"
      >
        <View
          style={[
            styles.card,
            {
              backgroundColor: palette.surface,
              borderRadius: radius.lg,
              alignSelf: isTablet ? 'center' : undefined,
              width: isTablet ? '100%' : undefined,
              maxWidth: isTablet ? formMaxWidth : undefined,
            },
          ]}
        >
          {product ? (
            <View style={{ alignItems: 'center', marginBottom: spacing.lg }}>
              <EdulynkMark size={56} />
              <Text
                style={[
                  typography.title,
                  { color: palette.textPrimary, marginTop: spacing.md, textAlign: 'center' },
                ]}
              >
                {PRODUCT.name}
              </Text>
              <Text
                style={[
                  typography.caption,
                  { color: palette.textSecondary, marginTop: spacing.xs, textAlign: 'center' },
                ]}
              >
                {PRODUCT.tagline}
              </Text>
            </View>
          ) : null}
          <Text style={[typography.title, { color: palette.textPrimary, marginBottom: spacing.sm }]}>
            Enter school code
          </Text>
          <Text style={[typography.body, { color: palette.textSecondary, marginBottom: spacing.lg }]}>
            Your school gives you a unique code. Enter it once and this app takes on that
            school’s name, colours, and data.
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
          <ChangeSchoolLink />
          {product ? (
            <Pressable
              onPress={() => void Linking.openURL(PRODUCT_WEBSITE_URL)}
              accessibilityRole="link"
              style={{ marginTop: spacing.lg, alignItems: 'center' }}
            >
              <Text style={{ color: colors.primary, fontWeight: '700' }}>
                {PRODUCT.websiteHost}
              </Text>
            </Pressable>
          ) : null}
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
