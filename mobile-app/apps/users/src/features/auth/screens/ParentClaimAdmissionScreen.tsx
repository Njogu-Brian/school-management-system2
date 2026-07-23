import { useVerifyClaimAdmission, type ClaimChild } from '@erp/core';
import { Button, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React, { useState } from 'react';
import { Text, View } from 'react-native';
import { ClaimField, ClaimScreenShell } from './claimUi';

interface Props {
  claimToken: string;
  onBack: () => void;
  onConfirmed: (children: ClaimChild[]) => void;
}

/**
 * Parent claim — step 3: enter a child's admission number. The backend confirms the
 * verified contact matches that student's parent_info and returns masked children.
 */
export const ParentClaimAdmissionScreen: React.FC<Props> = ({ claimToken, onBack, onConfirmed }) => {
  const { spacing, typography, radius } = useTheme();
  const [admission, setAdmission] = useState('');
  const [children, setChildren] = useState<ClaimChild[] | null>(null);

  const verify = useVerifyClaimAdmission();
  const busy = verify.isPending;
  const error = (verify.error as Error | null)?.message ?? null;
  const canSubmit = admission.trim().length > 0 && !busy;

  const handleLookup = async () => {
    if (!canSubmit) return;
    try {
      const data = await verify.mutateAsync({ claimToken, admissionNumber: admission });
      setChildren(data.children);
    } catch {
      setChildren(null);
    }
  };

  return (
    <ClaimScreenShell
      step={2}
      totalSteps={4}
      title="Find your child"
      subtitle="Enter your child's admission number so we can match your account."
      onBack={onBack}
      error={error}
    >
      <ClaimField
        label="Admission number"
        value={admission}
        onChangeText={(t) => {
          setAdmission(t);
          if (children) setChildren(null);
        }}
        placeholder="e.g. ADM/2024/001"
        icon="id-card-outline"
        autoCapitalize="characters"
        editable={!busy}
        onSubmitEditing={handleLookup}
      />

      {children && children.length > 0 ? (
        <View style={{ marginBottom: spacing.md }}>
          <Text style={{ color: 'rgba(255,255,255,0.7)', marginBottom: spacing.sm, fontSize: typography.caption.fontSize }}>
            We found the following {children.length === 1 ? 'child' : 'children'} linked to your contact. Confirm to continue.
          </Text>
          {children.map((child) => (
            <View
              key={child.id}
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                backgroundColor: 'rgba(255,255,255,0.06)',
                borderColor: 'rgba(255,255,255,0.14)',
                borderWidth: 1,
                borderRadius: radius.md,
                padding: spacing.md,
                marginBottom: spacing.sm,
              }}
            >
              <Ionicons name="person-circle-outline" size={28} color="#4B9FFF" style={{ marginRight: spacing.sm }} />
              <View style={{ flex: 1 }}>
                <Text style={{ color: '#fff', fontWeight: '700' }}>{child.first_name_masked}</Text>
                <Text style={{ color: 'rgba(255,255,255,0.6)', fontSize: typography.caption.fontSize }}>
                  {[child.class_name, child.admission_number].filter(Boolean).join(' · ') || 'Enrolled'}
                </Text>
              </View>
            </View>
          ))}
        </View>
      ) : null}

      {children && children.length > 0 ? (
        <Button label="This is correct — continue" onPress={() => onConfirmed(children)} disabled={busy} />
      ) : (
        <Button label="Find my child" onPress={handleLookup} loading={busy} disabled={!canSubmit} />
      )}

      <Text style={{ color: 'rgba(255,255,255,0.5)', marginTop: spacing.md, fontSize: typography.caption.fontSize }}>
        For your child's safety we only show partial names. If nothing matches, please contact the school office.
      </Text>
    </ClaimScreenShell>
  );
};
