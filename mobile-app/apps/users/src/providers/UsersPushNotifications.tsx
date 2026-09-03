import { useAuth, useInfiniteNotifications, usePushNotifications, UserRole } from '@erp/core';
import { useToast } from '@erp/ui';
import React, { useEffect, useRef } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

/**
 * Registers push for Users App roles and pops an in-app banner while the app is open.
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
  const seenIds = useRef<Set<string>>(new Set());
  const primed = useRef(false);

  usePushNotifications(enabled, ({ title, body }) => {
    setBanner({ title, body });
    showToast({ message: body ? `${title}: ${body}` : title, tone: 'info', durationMs: 5000 });
  });

  const unread = useInfiniteNotifications({ isRead: false, enabled: Boolean(user) && enabled });

  useEffect(() => {
    const items = unread.data?.pages?.[0]?.items ?? [];
    if (!primed.current) {
      items.forEach((item) => seenIds.current.add(item.id));
      primed.current = true;
      return;
    }
    const fresh = items.find((item) => !seenIds.current.has(item.id));
    if (!fresh) return;
    seenIds.current.add(fresh.id);
    const title = fresh.title || 'School alert';
    const body = fresh.body || '';
    setBanner({ title, body });
    showToast({ message: body ? `${title}: ${body}` : title, tone: 'info', durationMs: 5000 });
  }, [unread.data, showToast]);

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
