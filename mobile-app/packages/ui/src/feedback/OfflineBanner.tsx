import React from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export type OfflineBannerStatus = 'online' | 'offline' | 'reconnecting';

export interface OfflineBannerProps {
  status: OfflineBannerStatus;
  onRetry?: () => void;
}

export const OfflineBanner: React.FC<OfflineBannerProps> = ({ status, onRetry }) => {
  const { colors, fontSizes } = useTheme();

  if (status === 'online') {
    return null;
  }

  const message =
    status === 'reconnecting'
      ? 'Reconnecting… Cached data may be shown until sync completes.'
      : 'You are offline. Showing cached data where available.';

  const backgroundColor = status === 'reconnecting' ? colors.warning : colors.error;

  return (
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
  );
};

const styles = StyleSheet.create({
  banner: { paddingVertical: 6, paddingHorizontal: 12 },
  text: { color: '#fff', textAlign: 'center', fontWeight: '600' },
});
