import { admissionsApi, type ApplicationDetail } from '@erp/core';
import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from 'react-native';
import { showError, showSuccess } from '../../../shared/utils/feedback';

export interface InterviewTabProps {
  application: ApplicationDetail;
}

export const InterviewTab: React.FC<InterviewTabProps> = ({ application }) => {
  const interview = application.interview;
  const [outcome, setOutcome] = useState(interview?.outcome ?? 'passed');
  const [notes, setNotes] = useState(interview?.notes ?? '');
  const [mpesaCode, setMpesaCode] = useState('');
  const [amount, setAmount] = useState(
    interview?.fee_amount != null ? String(interview.fee_amount) : '',
  );
  const [scores, setScores] = useState<Record<string, string>>(() => {
    const map: Record<string, string> = {};
    (interview?.subjects ?? []).forEach((s) => {
      const key = String(s.id ?? s.code ?? s.name);
      const existing = interview?.scores?.find(
        (row) => row.subject_name === s.name || row.subject_code === s.code,
      );
      map[key] = existing?.score != null ? String(existing.score) : '';
    });
    return map;
  });
  const [saving, setSaving] = useState(false);
  const [allocating, setAllocating] = useState(false);

  const subjects = useMemo(() => interview?.subjects ?? [], [interview?.subjects]);

  const saveInterview = async () => {
    setSaving(true);
    try {
      const payloadScores = subjects.map((s) => {
        const key = String(s.id ?? s.code ?? s.name);
        return {
          subject_id: s.id,
          subject_name: s.name,
          subject_code: s.code,
          score: scores[key] !== '' ? Number(scores[key]) : null,
          out_of: 100,
        };
      });
      const res = await admissionsApi.recordInterview(application.id, {
        outcome,
        notes,
        interview_date: new Date().toISOString().slice(0, 10),
        scores: payloadScores,
      });
      if (!res.success) throw new Error(res.message || 'Failed to save interview');
      showSuccess('Interview saved');
    } catch (e) {
      showError('Interview', (e as Error).message);
    } finally {
      setSaving(false);
    }
  };

  const allocateFee = async () => {
    if (!mpesaCode.trim()) {
      showError('Fee', 'Enter the M-Pesa / paybill code.');
      return;
    }
    setAllocating(true);
    try {
      const res = await admissionsApi.allocateInterviewFee(application.id, {
        mpesa_code: mpesaCode.trim(),
        amount: amount !== '' ? Number(amount) : null,
      });
      if (!res.success) throw new Error(res.message || 'Failed to allocate fee');
      showSuccess('Interview fee allocated');
    } catch (e) {
      showError('Fee', (e as Error).message);
    } finally {
      setAllocating(false);
    }
  };

  return (
    <ScrollView contentContainerStyle={{ padding: 16, gap: 16 }}>
      <View style={{ gap: 4 }}>
        <Text style={{ fontWeight: '700', fontSize: 16 }}>Interview</Text>
        <Text style={{ color: '#666' }}>
          {interview?.required_label ?? application.interviewRequired ?? '—'}
          {interview?.outcome ? ` · ${interview.outcome}` : ''}
        </Text>
      </View>

      {application.interviewRequired !== 'not_applicable' && (
        <View style={{ gap: 10 }}>
          <Text style={{ fontWeight: '600' }}>Outcome</Text>
          {['passed', 'failed', 'deferred', 'skipped'].map((value) => (
            <Pressable key={value} onPress={() => setOutcome(value)}>
              <Text style={{ color: outcome === value ? '#5B2C8E' : '#333' }}>
                {outcome === value ? '● ' : '○ '}
                {value}
              </Text>
            </Pressable>
          ))}
          <TextInput
            value={notes}
            onChangeText={setNotes}
            placeholder="Notes"
            style={{ borderWidth: 1, borderColor: '#ddd', borderRadius: 8, padding: 10 }}
          />
          {subjects.map((s) => {
            const key = String(s.id ?? s.code ?? s.name);
            return (
              <View key={key} style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                <Text style={{ flex: 1 }}>{s.name}</Text>
                <TextInput
                  value={scores[key] ?? ''}
                  onChangeText={(v) => setScores((prev) => ({ ...prev, [key]: v }))}
                  keyboardType="decimal-pad"
                  placeholder="Score"
                  style={{
                    width: 80,
                    borderWidth: 1,
                    borderColor: '#ddd',
                    borderRadius: 8,
                    padding: 8,
                  }}
                />
                <Text>/ 100</Text>
              </View>
            );
          })}
          <Pressable
            onPress={() => void saveInterview()}
            style={{
              backgroundColor: '#5B2C8E',
              padding: 12,
              borderRadius: 10,
              alignItems: 'center',
            }}
          >
            {saving ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={{ color: '#fff', fontWeight: '600' }}>Save interview</Text>
            )}
          </Pressable>
        </View>
      )}

      <View style={{ gap: 8, marginTop: 8 }}>
        <Text style={{ fontWeight: '700' }}>Assessment / interview fee</Text>
        <Text style={{ color: '#666' }}>
          {interview?.fee_votehead_name ?? 'Assessment fee'} ·{' '}
          {interview?.fee_paid ? 'Paid' : 'Unpaid'}
          {interview?.fee_amount != null ? ` · KES ${interview.fee_amount}` : ''}
        </Text>
        {!interview?.fee_paid && (
          <>
            <TextInput
              value={amount}
              onChangeText={setAmount}
              keyboardType="decimal-pad"
              placeholder="Amount"
              style={{ borderWidth: 1, borderColor: '#ddd', borderRadius: 8, padding: 10 }}
            />
            <TextInput
              value={mpesaCode}
              onChangeText={setMpesaCode}
              placeholder="M-Pesa / paybill code"
              autoCapitalize="characters"
              style={{ borderWidth: 1, borderColor: '#ddd', borderRadius: 8, padding: 10 }}
            />
            <Pressable
              onPress={() => void allocateFee()}
              style={{
                backgroundColor: '#2f6fed',
                padding: 12,
                borderRadius: 10,
                alignItems: 'center',
              }}
            >
              {allocating ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={{ color: '#fff', fontWeight: '600' }}>Allocate & receipt</Text>
              )}
            </Pressable>
          </>
        )}
      </View>
    </ScrollView>
  );
};
