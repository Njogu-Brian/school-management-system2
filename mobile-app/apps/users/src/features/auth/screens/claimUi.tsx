import { useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { StatusBar } from 'expo-status-bar';
import React, { useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
  type TextInputProps,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

/** Shared dark gradient shell for the parent-claim flow (matches the login aesthetic). */
export const ClaimScreenShell: React.FC<{
  step: number;
  totalSteps: number;
  title: string;
  subtitle: string;
  onBack: () => void;
  error?: string | null;
  children: React.ReactNode;
}> = ({ step, totalSteps, title, subtitle, onBack, error, children }) => {
  const { colors, spacing, typography, radius } = useTheme();
  const insets = useSafeAreaInsets();

  return (
    <LinearGradient
      colors={[colors.primary, '#003366', '#0c1018']}
      start={{ x: 0.1, y: 0 }}
      end={{ x: 0.9, y: 1 }}
      style={styles.flex}
    >
      <StatusBar style="light" />
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView
          contentContainerStyle={{
            flexGrow: 1,
            paddingTop: insets.top + spacing.lg,
            paddingBottom: insets.bottom + spacing.xl,
            paddingHorizontal: spacing.lg,
          }}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          <Pressable onPress={onBack} hitSlop={10} style={{ flexDirection: 'row', alignItems: 'center', marginBottom: spacing.lg }}>
            <Ionicons name="chevron-back" size={22} color="#fff" />
            <Text style={{ color: '#fff', fontWeight: '600', marginLeft: 4 }}>Back</Text>
          </Pressable>

          <View style={{ flexDirection: 'row', marginBottom: spacing.lg }}>
            {Array.from({ length: totalSteps }).map((_, i) => (
              <View
                key={i}
                style={{
                  flex: 1,
                  height: 4,
                  borderRadius: 2,
                  marginRight: i < totalSteps - 1 ? 6 : 0,
                  backgroundColor: i < step ? colors.primaryOnDark ?? '#4B9FFF' : 'rgba(255,255,255,0.18)',
                }}
              />
            ))}
          </View>

          <Text
            style={{
              color: '#fff',
              fontSize: typography.headlineLarge.fontSize,
              fontWeight: '800',
              marginBottom: spacing.xs,
            }}
          >
            {title}
          </Text>
          <Text
            style={{
              color: 'rgba(255,255,255,0.7)',
              fontSize: typography.body.fontSize,
              marginBottom: spacing.lg,
            }}
          >
            {subtitle}
          </Text>

          {error ? (
            <View
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                backgroundColor: 'rgba(220,38,38,0.18)',
                borderColor: colors.error,
                borderWidth: 1,
                borderRadius: radius.control,
                padding: spacing.mdSm,
                marginBottom: spacing.md,
              }}
            >
              <Ionicons name="alert-circle" size={18} color={colors.error} />
              <Text style={{ color: '#fecaca', flex: 1, marginLeft: spacing.sm, fontSize: typography.caption.fontSize }}>
                {error}
              </Text>
            </View>
          ) : null}

          {children}
        </ScrollView>
      </KeyboardAvoidingView>
    </LinearGradient>
  );
};

/** Dark text field used across claim steps. */
export const ClaimField: React.FC<
  {
    label: string;
    icon: keyof typeof Ionicons.glyphMap;
    right?: React.ReactNode;
  } & TextInputProps
> = ({ label, icon, right, ...props }) => {
  const { spacing, typography, radius } = useTheme();
  const [focused, setFocused] = useState(false);
  return (
    <View style={{ marginBottom: spacing.md }}>
      <Text
        style={{
          color: 'rgba(255,255,255,0.55)',
          fontSize: typography.label.fontSize,
          fontWeight: typography.label.fontWeight,
          marginBottom: spacing.xs,
        }}
      >
        {label}
      </Text>
      <View
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          borderWidth: 1,
          borderColor: focused ? '#4B9FFF' : 'rgba(255,255,255,0.14)',
          borderRadius: radius.control,
          backgroundColor: 'rgba(255,255,255,0.06)',
          paddingHorizontal: spacing.mdSm,
          minHeight: 52,
        }}
      >
        <Ionicons name={icon} size={18} color="rgba(255,255,255,0.45)" style={{ marginRight: spacing.sm }} />
        <TextInput
          placeholderTextColor="rgba(255,255,255,0.35)"
          selectionColor="#4B9FFF"
          {...props}
          onFocus={(e) => {
            setFocused(true);
            props.onFocus?.(e);
          }}
          onBlur={(e) => {
            setFocused(false);
            props.onBlur?.(e);
          }}
          style={{
            flex: 1,
            color: '#fff',
            fontSize: typography.bodyLarge.fontSize,
            paddingVertical: spacing.mdSm,
          }}
        />
        {right}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
});
