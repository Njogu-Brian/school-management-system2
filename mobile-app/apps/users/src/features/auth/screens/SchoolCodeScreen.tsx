import { isCombinedApp, PRODUCT, PRODUCT_WEBSITE_URL, useSchool } from '@erp/core';
import {
  Button,
  ChangeSchoolLink,
  EdulynkMark,
  ScreenContainer,
  useAdaptiveLayout,
  useTheme,
} from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { StatusBar } from 'expo-status-bar';
import React, { useState } from 'react';
import {
  KeyboardAvoidingView,
  Linking,
  Platform,
  Pressable,
  ScrollView,
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

  const openUrl = (url: string) => {
    void Linking.openURL(url);
  };

  const form = (
    <>
      <Text
        style={[
          typography.title,
          { color: product ? PRODUCT.colors.ink : palette.textPrimary, marginBottom: spacing.sm },
        ]}
      >
        Enter school code
      </Text>
      <Text
        style={[
          typography.body,
          {
            color: product ? PRODUCT.colors.muted : palette.textSecondary,
            marginBottom: spacing.lg,
          },
        ]}
      >
        Your school gives you a unique code. Enter it once and this app takes on that school’s
        name, colours, and data.
      </Text>
      <TextInput
        autoCapitalize="characters"
        autoCorrect={false}
        value={code}
        onChangeText={setCode}
        placeholder="School code"
        placeholderTextColor={product ? PRODUCT.colors.muted : palette.textSecondary}
        style={[
          styles.input,
          {
            borderColor: product ? 'rgba(23,105,255,0.28)' : palette.border,
            backgroundColor: product ? PRODUCT.colors.mist : palette.surface,
            color: product ? PRODUCT.colors.ink : palette.textPrimary,
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
    </>
  );

  if (!product) {
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
            {form}
          </View>
        </KeyboardAvoidingView>
      </ScreenContainer>
    );
  }

  return (
    <View style={styles.flex}>
      <StatusBar style="light" />
      <LinearGradient
        colors={['#000000', PRODUCT.colors.navyDeep, PRODUCT.colors.navy]}
        start={{ x: 0.1, y: 0 }}
        end={{ x: 0.9, y: 1 }}
        style={StyleSheet.absoluteFill}
      />
      <KeyboardAvoidingView
        style={styles.flex}
        behavior="padding"
        keyboardVerticalOffset={Platform.OS === 'ios' ? insets.top : 0}
      >
        <ScrollView
          contentContainerStyle={{
            flexGrow: 1,
            paddingTop: insets.top + spacing.xl,
            paddingBottom: Math.max(insets.bottom, 16) + spacing.lg,
            paddingHorizontal: 24,
            justifyContent: 'center',
          }}
          keyboardShouldPersistTaps="handled"
        >
          <View
            style={{
              alignSelf: isTablet ? 'center' : undefined,
              width: isTablet ? '100%' : undefined,
              maxWidth: isTablet ? formMaxWidth : undefined,
            }}
          >
            <View style={{ alignItems: 'center', marginBottom: spacing.xl }}>
              <EdulynkMark size={96} variant="lockup" />
            </View>

            <View
              style={[
                styles.card,
                {
                  backgroundColor: '#FFFFFF',
                  borderRadius: radius.lg,
                },
              ]}
            >
              {form}
            </View>

            <View style={{ marginTop: spacing.xl, alignItems: 'center', gap: spacing.sm }}>
              <Text
                style={{
                  color: 'rgba(255,255,255,0.55)',
                  fontWeight: '700',
                  fontSize: typography.caption.fontSize,
                  letterSpacing: 0.8,
                  textTransform: 'uppercase',
                }}
              >
                Need a school code?
              </Text>
              <Pressable
                onPress={() => openUrl(PRODUCT.contactUrl)}
                accessibilityRole="link"
                style={styles.contactRow}
              >
                <Ionicons name="chatbubbles-outline" size={18} color={PRODUCT.colors.cyan} />
                <Text style={styles.contactText}>Contact Edulynk</Text>
              </Pressable>
              <Pressable
                onPress={() => openUrl(`mailto:${PRODUCT.salesEmail}`)}
                accessibilityRole="link"
                style={styles.contactRow}
              >
                <Ionicons name="mail-outline" size={18} color={PRODUCT.colors.cyan} />
                <Text style={styles.contactText}>{PRODUCT.salesEmail}</Text>
              </Pressable>
              <Pressable
                onPress={() => openUrl(`tel:${PRODUCT.phone}`)}
                accessibilityRole="link"
                style={styles.contactRow}
              >
                <Ionicons name="call-outline" size={18} color={PRODUCT.colors.cyan} />
                <Text style={styles.contactText}>{PRODUCT.phoneDisplay}</Text>
              </Pressable>
              <Pressable
                onPress={() => openUrl(PRODUCT_WEBSITE_URL)}
                accessibilityRole="link"
                style={styles.contactRow}
              >
                <Ionicons name="globe-outline" size={18} color={PRODUCT.colors.cyan} />
                <Text style={styles.contactText}>{PRODUCT.websiteHost}</Text>
              </Pressable>
            </View>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  card: { padding: 24 },
  input: {
    borderWidth: 1,
    paddingHorizontal: 14,
    paddingVertical: Platform.OS === 'ios' ? 14 : 10,
    fontSize: 18,
    letterSpacing: 1,
  },
  contactRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 4,
  },
  contactText: {
    color: '#FFFFFF',
    fontWeight: '700',
    fontSize: 15,
  },
});
