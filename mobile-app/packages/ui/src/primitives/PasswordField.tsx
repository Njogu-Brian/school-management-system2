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
  currentError?: string | null;
  confirmError?: string | null;
  autoComplete?: 'password-new' | 'password';
  /** Skip OS password-manager overlays that fight prefilled / focused fields. */
  disableAutofill?: boolean;
};

function EyeToggle({ visible, onPress, color }: { visible: boolean; onPress: () => void; color: string }) {
  return (
    <Pressable onPress={onPress} hitSlop={8} accessibilityLabel={visible ? 'Hide password' : 'Show password'}>
      <Ionicons name={visible ? 'eye-off-outline' : 'eye-outline'} size={20} color={color} />
    </Pressable>
  );
}

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
  currentError,
  confirmError,
  autoComplete = 'password-new',
  disableAutofill = false,
}) => {
  const { colors, palette, spacing, typography } = useTheme();
  const [currentVisible, setCurrentVisible] = useState(false);
  const [nextVisible, setNextVisible] = useState(false);
  const [confirmVisible, setConfirmVisible] = useState(false);
  const checks = useMemo(() => passwordChecklist(value), [value]);

  return (
    <View>
      {username ? (
        <TextField
          label="Account"
          value={username}
          editable={false}
          autoComplete={disableAutofill ? 'off' : 'username'}
          textContentType={disableAutofill ? 'none' : 'username'}
          importantForAutofill={disableAutofill ? 'no' : undefined}
        />
      ) : null}
      {showCurrent ? (
        <TextField
          label={currentLabel}
          value={currentValue ?? ''}
          onChangeText={onCurrentChange}
          secureTextEntry={!currentVisible}
          autoCapitalize="none"
          autoCorrect={false}
          autoComplete={disableAutofill ? 'off' : 'password'}
          textContentType={disableAutofill ? 'none' : 'password'}
          importantForAutofill={disableAutofill ? 'no' : undefined}
          error={currentError}
          rightSlot={
            <EyeToggle visible={currentVisible} onPress={() => setCurrentVisible((v) => !v)} color={palette.textMuted} />
          }
        />
      ) : null}
      <TextField
        label={label}
        value={value}
        onChangeText={onChangeText}
        secureTextEntry={!nextVisible}
        autoCapitalize="none"
        autoCorrect={false}
        autoComplete={disableAutofill ? 'off' : autoComplete}
        textContentType={disableAutofill ? 'none' : 'newPassword'}
        passwordRules={disableAutofill ? undefined : 'minlength: 8; required: upper; required: lower; required: digit;'}
        importantForAutofill={disableAutofill ? 'no' : 'yes'}
        error={error}
        rightSlot={
          <EyeToggle visible={nextVisible} onPress={() => setNextVisible((v) => !v)} color={palette.textMuted} />
        }
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
            setNextVisible(true);
            setConfirmVisible(true);
          }}
          style={{ marginTop: spacing.xs }}
        >
          <Text style={{ color: colors.primary, fontWeight: '700' }}>Suggest a strong password</Text>
        </Pressable>
      </View>
      {showConfirm ? (
        <TextField
          label="Confirm new password"
          value={confirmValue ?? ''}
          onChangeText={onConfirmChange}
          secureTextEntry={!confirmVisible}
          autoCapitalize="none"
          autoCorrect={false}
          autoComplete={disableAutofill ? 'off' : 'password-new'}
          textContentType={disableAutofill ? 'none' : 'newPassword'}
          importantForAutofill={disableAutofill ? 'no' : undefined}
          error={confirmError}
          rightSlot={
            <EyeToggle visible={confirmVisible} onPress={() => setConfirmVisible((v) => !v)} color={palette.textMuted} />
          }
        />
      ) : null}
    </View>
  );
};
