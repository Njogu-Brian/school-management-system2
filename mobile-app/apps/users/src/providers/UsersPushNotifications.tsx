import { queryKeys, useAuth, usePushNotifications, UserRole } from '@erp/core';
import { useToast } from '@erp/ui';
import { useQueryClient } from '@tanstack/react-query';
import React, { useEffect } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

/**
 * Registers push for Users App roles and pops an in-app banner while the app is open.
 *
 * The banner used to be driven by polling `GET /notifications` every 20 seconds
 * and diffing the ids against a seen-set — on every screen, for every signed-in
 * user, alongside push that was already delivering the same events. The push
 * foreground callback now does both jobs: it shows the banner and invalidates the
 * notification queries so the unread badge and any open list refresh straight
 * away.
 */
export const UsersPushNotifications: React.FC = () => {
  const { user } = useAuth();
  const enabled =
    user?.role === UserRole.TEACHER ||
    user?.role === UserRole.SENIOR_TEACHER ||
    user?.role === UserRole.SUPERVISOR ||
    user?.role === UserRole.PARENT ||
    user?.role === UserRole.GUARDIAN ||
    user?.role === UserRole.STUDENT ||
    user?.role === UserRole.DRIVER ||
    user?.role === UserRole.TRANSPORT;
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
    <Pressable
      onPress={() => setBanner(null)}
      style={[styles.banner, { top: insets.top + 72 }]}
    >
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
