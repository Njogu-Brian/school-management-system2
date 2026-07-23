import { useMutation } from '@tanstack/react-query';
import {
  parentClaimApi,
  type ClaimAdmissionData,
  type ClaimChannel,
  type ClaimOtpVerifyData,
} from '../../api/parentClaim.api';
import type { ApiLoginData } from '../../types';

export function useRequestClaimOtp() {
  return useMutation({
    mutationFn: async (args: { channel: ClaimChannel; identifier: string }) => {
      const res = await parentClaimApi.requestOtp(args.channel, args.identifier);
      if (!res.success) throw new Error(res.message || 'Could not send verification code.');
      return res.message ?? '';
    },
  });
}

export function useVerifyClaimOtp() {
  return useMutation<ClaimOtpVerifyData, Error, { channel: ClaimChannel; identifier: string; code: string }>({
    mutationFn: async (args) => {
      const res = await parentClaimApi.verifyOtp(args.channel, args.identifier, args.code);
      if (!res.success || !res.data) throw new Error(res.message || 'Invalid or expired code.');
      return res.data;
    },
  });
}

export function useVerifyClaimAdmission() {
  return useMutation<ClaimAdmissionData, Error, { claimToken: string; admissionNumber: string }>({
    mutationFn: async (args) => {
      const res = await parentClaimApi.verifyAdmission(args.claimToken, args.admissionNumber);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'We could not match these details.');
      }
      return res.data;
    },
  });
}

export function useCompleteParentClaim() {
  return useMutation<
    ApiLoginData,
    Error,
    { claimToken: string; name: string; password: string; passwordConfirmation: string; email?: string }
  >({
    mutationFn: async (args) => {
      const res = await parentClaimApi.complete(args);
      if (!res.success || !res.data) throw new Error(res.message || 'Could not complete signup.');
      return res.data;
    },
  });
}
