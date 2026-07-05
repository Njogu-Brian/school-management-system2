import {
  syncKindLabel,
  useSyncQueue,
  type SyncConflictRow,
  type SyncQueueItem,
} from '@erp/core';
import { Button, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import {
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

interface Props {
  visible: boolean;
  onClose: () => void;
}

function ConflictItem({
  item,
  onKeepLocal,
  onUseServer,
}: {
  item: SyncQueueItem;
  onKeepLocal: () => void;
  onUseServer: () => void;
}) {
  const { palette, spacing, fontSizes, radius } = useTheme();
  const rows = item.conflicts ?? [];

  return (
    <View
      style={[
        styles.card,
        { borderColor: palette.border, backgroundColor: palette.surface, borderRadius: radius.lg },
      ]}
    >
      <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: 4 }}>
        {item.label ?? syncKindLabel(item.kind)}
      </Text>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.sm }}>
        Queued {new Date(item.createdAt).toLocaleString()}
      </Text>

      {rows.map((row: SyncConflictRow) => (
        <View key={String(row.id)} style={[styles.row, { borderBottomColor: palette.border }]}>
          <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: fontSizes.sm }}>
            {row.label}
          </Text>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
            Yours: {row.localValue} · Server: {row.serverValue}
          </Text>
          {row.baseValue ? (
            <Text style={{ color: palette.textMuted, fontSize: 10 }}>When queued: {row.baseValue}</Text>
          ) : null}
        </View>
      ))}

      <View style={{ flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm }}>
        <Button label="Keep mine" onPress={onKeepLocal} />
        <Button label="Use server" variant="secondary" onPress={onUseServer} />
      </View>
    </View>
  );
}

export const SyncConflictSheet: React.FC<Props> = ({ visible, onClose }) => {
  const { palette, spacing, fontSizes } = useTheme();
  const { items, loading, forceLocal, discardItem, refresh, process } = useSyncQueue();

  const conflictItems = useMemo(
    () => items.filter((i) => i.status === 'conflict' || i.status === 'failed'),
    [items],
  );

  const pendingItems = useMemo(
    () => items.filter((i) => i.status === 'pending'),
    [items],
  );

  return (
    <Modal visible={visible} animationType="slide" transparent onRequestClose={onClose}>
      <View style={styles.overlay}>
        <View style={[styles.sheet, { backgroundColor: palette.surface }]}>
          <View style={styles.header}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: fontSizes.lg }}>
              Sync queue
            </Text>
            <Pressable onPress={onClose}>
              <Text style={{ color: palette.textSecondary, fontWeight: '600' }}>Close</Text>
            </Pressable>
          </View>

          <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
            {conflictItems.length === 0 && pendingItems.length === 0 ? (
              <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>
                No pending sync items.
              </Text>
            ) : null}

            {conflictItems.map((item) => (
              <ConflictItem
                key={item.id}
                item={item}
                onKeepLocal={() => void forceLocal(item.id).then(() => void refresh())}
                onUseServer={() => void discardItem(item.id)}
              />
            ))}

            {pendingItems.length > 0 ? (
              <>
                <Text
                  style={{
                    color: palette.textPrimary,
                    fontWeight: '700',
                    marginTop: spacing.md,
                    marginBottom: spacing.sm,
                  }}
                >
                  Pending ({pendingItems.length})
                </Text>
                {pendingItems.map((item) => (
                  <View
                    key={item.id}
                    style={[styles.pendingRow, { borderColor: palette.border }]}
                  >
                    <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>
                      {item.label ?? syncKindLabel(item.kind)}
                    </Text>
                    <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
                      {new Date(item.createdAt).toLocaleString()}
                    </Text>
                  </View>
                ))}
                <Button
                  label="Sync now"
                  onPress={() => void process().then(() => void refresh())}
                  loading={loading}
                  style={{ marginTop: spacing.sm }}
                />
              </>
            ) : null}
          </ScrollView>
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  overlay: { flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.45)' },
  sheet: { maxHeight: '85%', borderTopLeftRadius: 16, borderTopRightRadius: 16 },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: '#e5e7eb',
  },
  card: { borderWidth: StyleSheet.hairlineWidth, padding: 12, marginBottom: 12 },
  row: { paddingVertical: 8, borderBottomWidth: StyleSheet.hairlineWidth },
  pendingRow: {
    borderWidth: StyleSheet.hairlineWidth,
    borderRadius: 8,
    padding: 10,
    marginBottom: 8,
  },
});
