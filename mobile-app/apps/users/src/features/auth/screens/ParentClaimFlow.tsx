import type { ClaimChannel, ClaimChild } from '@erp/core';
import React, { useState } from 'react';
import { ParentClaimAdmissionScreen } from './ParentClaimAdmissionScreen';
import { ParentClaimOtpScreen } from './ParentClaimOtpScreen';
import { ParentClaimPasswordScreen } from './ParentClaimPasswordScreen';

type Step = 'otp' | 'admission' | 'password';

interface ClaimState {
  channel: ClaimChannel;
  identifier: string;
  claimToken: string;
  children: ClaimChild[];
}

/**
 * Self-contained first-time parent claim flow. Rendered as a full-screen overlay from
 * the login screen (before navigation exists). On completion, the session is established
 * by ParentClaimPasswordScreen and the root gate swaps to the authenticated shell.
 */
export const ParentClaimFlow: React.FC<{ onExit: () => void }> = ({ onExit }) => {
  const [step, setStep] = useState<Step>('otp');
  const [state, setState] = useState<ClaimState>({
    channel: 'phone',
    identifier: '',
    claimToken: '',
    children: [],
  });

  if (step === 'admission') {
    return (
      <ParentClaimAdmissionScreen
        claimToken={state.claimToken}
        onBack={() => setStep('otp')}
        onConfirmed={(children) => {
          setState((s) => ({ ...s, children }));
          setStep('password');
        }}
      />
    );
  }

  if (step === 'password') {
    return (
      <ParentClaimPasswordScreen
        claimToken={state.claimToken}
        channel={state.channel}
        identifier={state.identifier}
        onBack={() => setStep('admission')}
      />
    );
  }

  return (
    <ParentClaimOtpScreen
      onBack={onExit}
      onVerified={({ channel, identifier, claimToken }) => {
        setState((s) => ({ ...s, channel, identifier, claimToken }));
        setStep('admission');
      }}
    />
  );
};
