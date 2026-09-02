import { Ionicons } from '@expo/vector-icons';
import React, { useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import {
  generatePassword,
  passwordChecklist,
} from './passwordPolicy';
import { TextField } from './TextField';
import { useTheme } from '../theme/ThemeContext';

export { generatePassword, isStrongPassword, passwordChecklist } from './passwordPolicy';

type Props = {
  label?: string;
  value: string;
  onChangeText: (value: string) => void;
  confirmValue?: string;
  onConfirmChange?: (value: string) => void;
  showConfirm?: boolean;
  showCurrent?: boolean;
  currentValue?: string;
  onCurrentChange?: (value: string) => void;
  currentLabel?: string;
  username?: string;
  error?: string | null;
  autoComplete?: 'password-new' | 'password';
};

export const PasswordField: React.FC<Props> = ({
  label = 'New password',
  value,
  onChangeText,
  confirmValue,
  onConfirmChange,
  showConfirm = false,
  showCurrent = false,
  currentValue,
  onCurrentChange,
  currentLabel = 'Current password',
  username,
  error,
  autoComplete = 'password-new',
}) => {
  const { colors, palette, spacing, typography } = useTheme();
  const [visible, setVisible] = useState(false);
  const checks = useMemo(() => passwordChecklist(value), [value]);

  const eye = () => (
    <Pressable onPress={() => setVisible((v) => !v)} hitSlop={8} accessibilityLabel={visible ? 'Hide password' : 'Show password'}>
      <Ionicons name={visible ? 'eye-off-outline' : 'eye-outline'} size={20} color={palette.textMuted} />
    </Pressable>
  );

  return (
    <View>
      {username ? (
        <TextField
          label="Account"
          value={username}
          editable={false}
          autoComplete="username"
          textContentType="username"
        />
      ) : null}
      {showCurrent ? (
        <TextField
          label={currentLabel}
          value={currentValue ?? ''}
          onChangeText={onCurrentChange}
          secureTextEntry={!visible}
          autoCapitalize="none"
          autoCorrect={false}
          autoComplete="password"
          textContentType="password"
          rightSlot={eye()}
        />
      ) : null}
      <TextField
        label={label}
        value={value}
        onChangeText={onChangeText}
        secureTextEntry={!visible}
        autoCapitalize="none"
        autoCorrect={false}
        autoComplete={autoComplete}
        textContentType="newPassword"
        passwordRules="minlength: 8; required: upper; required: lower; required: digit;"
        importantForAutofill="yes"
        rightSlot={eye()}
        error={error}
      />
      <View style={{ marginBottom: spacing.md }}>
        {checks.map((item) => (
          <Text
            key={item.id}
            style={{
              color: item.ok ? colors.success : value.length ? colors.error : palette.textMuted,
              fontSize: typography.caption.fontSize,
              marginBottom: 2,
            }}
          >
            {item.ok ? '✓' : '○'} {item.label}
          </Text>
        ))}
        <Pressable
          onPress={() => {
            const next = generatePassword();
            onChangeText(next);
            onConfirmChange?.(next);
            setVisible(true);
          }}
          style={{ marginTop: spacing.xs }}
        >
          <Text style={{ color: colors.primary, fontWeight: '700' }}>Generate a strong password</Text>
        </Pressable>
        <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
          After you save, Google Password Manager can store it on this device.
        </Text>
      </View>
      {showConfirm ? (
        <TextField
          label="Confirm new password"
          value={confirmValue ?? ''}
          onChangeText={onConfirmChange}
          secureTextEntry={!visible}
          autoCapitalize="none"
          autoCorrect={false}
          autoComplete="password-new"
          textContentType="newPassword"
          rightSlot={eye()}
        />
      ) : null}
    </View>
  );
};
