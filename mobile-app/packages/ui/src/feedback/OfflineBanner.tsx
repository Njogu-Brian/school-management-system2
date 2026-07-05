import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export type OfflineBannerStatus = 'online' | 'offline' | 'reconnecting';

export interface OfflineBannerProps {
  status: OfflineBannerStatus;
  onRetry?: () => void;
  pendingCount?: number;
  conflictCount?: number;
  onReviewConflicts?: () => void;
}

export const OfflineBanner: React.FC<OfflineBannerProps> = ({
  status,
  onRetry,
  pendingCount = 0,
  conflictCount = 0,
  onReviewConflicts,
}) => {
  const { colors, fontSizes } = useTheme();

  if (status === 'online' && pendingCount === 0 && conflictCount === 0) {
    return null;
  }

  if (status === 'online' && conflictCount > 0) {
    return (
      <Pressable
        onPress={onReviewConflicts ?? onRetry}
        style={[styles.banner, { backgroundColor: colors.warning }]}
        accessibilityRole="alert"
      >
        <Text style={[styles.text, { fontSize: fontSizes.xs }]}>
          {conflictCount} sync conflict{conflictCount === 1 ? '' : 's'} need review. Tap to resolve.
        </Text>
      </Pressable>
    );
  }

  if (status === 'online' && pendingCount > 0) {
    return (
      <Pressable
        onPress={onRetry}
        style={[styles.banner, { backgroundColor: colors.primary }]}
        accessibilityRole="alert"
      >
        <Text style={[styles.text, { fontSize: fontSizes.xs }]}>
          {pendingCount} change{pendingCount === 1 ? '' : 's'} waiting to sync. Tap to retry.
        </Text>
      </Pressable>
    );
  }

  const message =
    status === 'reconnecting'
      ? 'Reconnecting… Cached data may be shown until sync completes.'
      : 'You are offline. Edits are saved locally and will sync when you reconnect.';

  const backgroundColor = status === 'reconnecting' ? colors.warning : colors.error;

  return (
    <View>
      <Pressable
        onPress={onRetry}
        style={[styles.banner, { backgroundColor }]}
        accessibilityRole="alert"
        accessibilityHint={onRetry ? 'Tap to retry sync' : undefined}
      >
        <Text style={[styles.text, { fontSize: fontSizes.xs }]}>
          {message}
          {onRetry ? ' Tap to refresh.' : ''}
        </Text>
      </Pressable>
      {pendingCount > 0 ? (
        <View style={[styles.subBanner, { backgroundColor: colors.primary }]}>
          <Text style={[styles.text, { fontSize: fontSizes.xs }]}>
            {pendingCount} unsynced change{pendingCount === 1 ? '' : 's'} queued.
          </Text>
        </View>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  banner: { paddingVertical: 6, paddingHorizontal: 12 },
  subBanner: { paddingVertical: 4, paddingHorizontal: 12 },
  text: { color: '#fff', textAlign: 'center', fontWeight: '600' },
});
