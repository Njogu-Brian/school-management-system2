import { runApiDiagnostics, type ApiProbeResult } from '@erp/core';
import { ScreenContainer, useTheme } from '@erp/ui';
import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  View,
} from 'react-native';

interface ApiDiagnosticsScreenProps {
  onClose: () => void;
}

function groupResults(results: ApiProbeResult[]): Array<{ group: string; items: ApiProbeResult[] }> {
  const map = new Map<string, ApiProbeResult[]>();
  for (const row of results) {
    const list = map.get(row.group) ?? [];
    list.push(row);
    map.set(row.group, list);
  }
  return Array.from(map.entries()).map(([group, items]) => ({ group, items }));
}

export const ApiDiagnosticsScreen: React.FC<ApiDiagnosticsScreenProps> = ({ onClose }) => {
  const { colors, spacing, typography, palette } = useTheme();
  const [results, setResults] = useState<ApiProbeResult[] | null>(null);
  const [running, setRunning] = useState(false);

  const run = useCallback(async () => {
    setRunning(true);
    try {
      const probes = await runApiDiagnostics();
      setResults(probes);
    } finally {
      setRunning(false);
    }
  }, []);

  useEffect(() => {
    void run();
  }, [run]);

  const grouped = results ? groupResults(results) : [];
  const passCount = results?.filter((r) => r.ok).length ?? 0;
  const failCount = results?.filter((r) => !r.ok).length ?? 0;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          justifyContent: 'space-between',
          paddingHorizontal: spacing.md,
          paddingVertical: spacing.sm,
          borderBottomWidth: 1,
          borderBottomColor: palette.border,
        }}
      >
        <View>
          <Text style={{ fontSize: typography.title.fontSize, fontWeight: '700', color: palette.textPrimary }}>
            API Diagnostics
          </Text>
          <Text style={{ fontSize: typography.body.fontSize, color: palette.textSecondary, marginTop: 2 }}>
            Development only — probes live endpoints with your session token.
          </Text>
        </View>
        <Pressable onPress={onClose} hitSlop={12}>
          <Text style={{ color: colors.primary, fontWeight: '600' }}>Close</Text>
        </Pressable>
      </View>

      <View
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          gap: spacing.md,
          paddingHorizontal: spacing.md,
          paddingVertical: spacing.sm,
        }}
      >
        {running ? (
          <ActivityIndicator color={colors.primary} />
        ) : (
          <Pressable
            onPress={() => void run()}
            style={{
              backgroundColor: colors.primary,
              paddingHorizontal: spacing.md,
              paddingVertical: spacing.xs,
              borderRadius: 8,
            }}
          >
            <Text style={{ color: '#fff', fontWeight: '600' }}>Re-run probes</Text>
          </Pressable>
        )}
        {results ? (
          <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>
            {passCount} healthy · {failCount} failed
          </Text>
        ) : null}
      </View>

      <FlatList
        data={grouped}
        keyExtractor={(item) => item.group}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        renderItem={({ item }) => (
          <View style={{ marginBottom: spacing.lg }}>
            <Text
              style={{
                fontSize: typography.titleSmall.fontSize,
                fontWeight: '700',
                color: palette.textPrimary,
                marginBottom: spacing.xs,
              }}
            >
              {item.group}
            </Text>
            {item.items.map((probe) => (
              <View
                key={probe.endpoint}
                style={{
                  borderWidth: 1,
                  borderColor: probe.ok ? '#22c55e44' : '#ef444444',
                  backgroundColor: probe.ok ? '#22c55e11' : '#ef444411',
                  borderRadius: 8,
                  padding: spacing.sm,
                  marginBottom: spacing.xs,
                }}
              >
                <Text style={{ fontWeight: '600', color: palette.textPrimary }}>
                  {probe.ok ? '✓' : '✗'} {probe.label}
                </Text>
                <Text
                  style={{
                    fontSize: typography.body.fontSize,
                    color: palette.textSecondary,
                    marginTop: 2,
                    fontFamily: 'monospace',
                  }}
                >
                  {probe.endpoint}
                </Text>
                {!probe.ok ? (
                  <Text style={{ fontSize: typography.body.fontSize, color: '#b91c1c', marginTop: 4 }}>
                    {probe.status ? `HTTP ${probe.status} — ` : ''}
                    {probe.message}
                  </Text>
                ) : (
                  <Text style={{ fontSize: typography.body.fontSize, color: palette.textSecondary, marginTop: 4 }}>
                    {probe.durationMs}ms
                  </Text>
                )}
              </View>
            ))}
          </View>
        )}
      />
    </ScreenContainer>
  );
};
