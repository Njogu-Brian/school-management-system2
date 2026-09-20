import { queryKeys, useAuth, usePushNotifications } from '@erp/core';
import { useToast } from '@erp/ui';
import { useQueryClient } from '@tanstack/react-query';
import React, { useEffect } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

/**
 * Registers push for every signed-in combined-app role and shows an in-app banner.
 *
 * See UsersPushNotifications: the 20-second notification poll this used to run
 * duplicated push, so the push callback now shows the banner and invalidates the
 * notification queries instead.
 */
export const IosPushNotifications: React.FC = () => {
  const { user } = useAuth();
  const enabled = Boolean(user);
  const { showToast } = useToast();
  const [banner, setBanner] = React.useState<{ title: string; body: string } | null>(null);
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();

  usePushNotifications(enabled, ({ title, body }) => {
    setBanner({ title, body });
    showToast({ message: body ? `${title}: ${body}` : title, tone: 'info', durationMs: 5000 });
    void queryClient.invalidateQueries({ queryKey: queryKeys.notifications.all });
  });

  useEffect(() => {
    if (!banner) return;
    const t = setTimeout(() => setBanner(null), 6000);
    return () => clearTimeout(t);
  }, [banner]);

  if (!banner) return null;

  return (
    <Pressable onPress={() => setBanner(null)} style={[styles.banner, { top: insets.top + 72 }]}>
      <Text style={styles.title} numberOfLines={1}>
        {banner.title}
      </Text>
      {banner.body ? (
        <Text style={styles.body} numberOfLines={2}>
          {banner.body}
        </Text>
      ) : null}
    </Pressable>
  );
};

const styles = StyleSheet.create({
  banner: {
    position: 'absolute',
    left: 12,
    right: 12,
    zIndex: 9999,
    backgroundColor: '#0c1018',
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: 'rgba(75,159,255,0.45)',
  },
  title: { color: '#fff', fontWeight: '800', fontSize: 14 },
  body: { color: 'rgba(255,255,255,0.8)', marginTop: 4, fontSize: 13 },
});
