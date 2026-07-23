import {
  useRequestClaimOtp,
  useVerifyClaimOtp,
  type ClaimChannel,
} from '@erp/core';
import { Button, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { showSuccess } from '../../shared/utils/feedback';
import { ClaimField, ClaimScreenShell } from './claimUi';

interface Props {
  onBack: () => void;
  onVerified: (args: { channel: ClaimChannel; identifier: string; claimToken: string }) => void;
}

/**
 * Parent claim — step 1 & 2: choose phone/email, request an OTP, then verify it.
 * On success we receive a short-lived claim session token from the backend.
 */
export const ParentClaimOtpScreen: React.FC<Props> = ({ onBack, onVerified }) => {
  const { colors, spacing, typography, radius } = useTheme();
  const [channel, setChannel] = useState<ClaimChannel>('phone');
  const [identifier, setIdentifier] = useState('');
  const [code, setCode] = useState('');
  const [otpSent, setOtpSent] = useState(false);

  const requestOtp = useRequestClaimOtp();
  const verifyOtp = useVerifyClaimOtp();

  const busy = requestOtp.isPending || verifyOtp.isPending;
  const error = (requestOtp.error as Error | null)?.message ?? (verifyOtp.error as Error | null)?.message ?? null;
  const canSend = identifier.trim().length > 3 && !busy;
  const canVerify = otpSent && code.trim().length === 6 && !busy;

  const handleSend = async () => {
    if (!canSend) return;
    try {
      const message = await requestOtp.mutateAsync({ channel, identifier });
      setOtpSent(true);
      showSuccess('Code sent', message || `Enter the 6-digit code sent to your ${channel}.`);
    } catch {
      /* surfaced via error banner */
    }
  };

  const handleVerify = async () => {
    if (!canVerify) return;
    try {
      const data = await verifyOtp.mutateAsync({ channel, identifier, code });
      onVerified({ channel, identifier: identifier.trim(), claimToken: data.claim_token });
    } catch {
      /* surfaced via error banner */
    }
  };

  return (
    <ClaimScreenShell
      step={1}
      totalSteps={4}
      title="Claim parent access"
      subtitle="Verify a phone or email registered with the school to get started."
      onBack={onBack}
      error={error}
    >
      <View
        style={{
          flexDirection: 'row',
          backgroundColor: 'rgba(255,255,255,0.06)',
          borderRadius: radius.control,
          padding: 4,
          marginBottom: spacing.md,
        }}
      >
        {(['phone', 'email'] as const).map((c) => {
          const active = channel === c;
          return (
            <Pressable
              key={c}
              onPress={() => {
                setChannel(c);
                setOtpSent(false);
                setCode('');
              }}
              style={{
                flex: 1,
                alignItems: 'center',
                paddingVertical: 10,
                borderRadius: radius.md,
                backgroundColor: active ? colors.primary : 'transparent',
              }}
            >
              <Text style={{ color: active ? '#fff' : 'rgba(255,255,255,0.65)', fontWeight: '700', fontSize: typography.caption.fontSize }}>
                {c === 'phone' ? 'Phone (SMS)' : 'Email'}
              </Text>
            </Pressable>
          );
        })}
      </View>

      <ClaimField
        label={channel === 'phone' ? 'Phone number' : 'Email address'}
        value={identifier}
        onChangeText={(t) => {
          setIdentifier(t);
          if (otpSent) setOtpSent(false);
        }}
        placeholder={channel === 'phone' ? '07XX XXX XXX' : 'you@example.com'}
        icon={channel === 'phone' ? 'call-outline' : 'mail-outline'}
        autoCapitalize="none"
        keyboardType={channel === 'phone' ? 'phone-pad' : 'email-address'}
        editable={!busy}
      />

      {otpSent ? (
        <ClaimField
          label="6-digit code"
          value={code}
          onChangeText={(t) => setCode(t.replace(/\D/g, '').slice(0, 6))}
          placeholder="000000"
          icon="keypad-outline"
          keyboardType="number-pad"
          editable={!busy}
          onSubmitEditing={handleVerify}
        />
      ) : null}

      <Button
        label={otpSent ? 'Verify code' : 'Send code'}
        onPress={otpSent ? handleVerify : handleSend}
        loading={busy}
        disabled={otpSent ? !canVerify : !canSend}
      />

      {otpSent ? (
        <Pressable onPress={handleSend} disabled={busy} style={{ marginTop: spacing.sm }}>
          <Text style={{ color: colors.primaryOnDark ?? '#4B9FFF', textAlign: 'center', fontWeight: '600' }}>
            Resend code
          </Text>
        </Pressable>
      ) : null}
    </ClaimScreenShell>
  );
};
