import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { Staff360Header } from './Staff360Header';
import type { Staff360HeaderData, Staff360TabId } from './types';

export interface Staff360Tab {
  id: Staff360TabId;
  label: string;
}

export interface Staff360LayoutProps {
  header: Staff360HeaderData;
  tabs: Staff360Tab[];
  activeTab: Staff360TabId;
  onTabChange: (tab: Staff360TabId) => void;
  children: React.ReactNode;
  onBack?: () => void;
}

export const Staff360Layout: React.FC<Staff360LayoutProps> = ({
  header,
  tabs,
  activeTab,
  onTabChange,
  children,
  onBack,
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <View style={styles.flex}>
      {onBack ? (
        <View style={[styles.topBar, { paddingHorizontal: spacing.md, paddingTop: spacing.sm }]}>
          <Pressable
            onPress={onBack}
            accessibilityRole="button"
            style={({ pressed }) => [{ opacity: pressed ? 0.7 : 1, padding: spacing.xs }]}
          >
            <Text style={{ fontSize: fontSizes.lg, color: palette.textPrimary }}>←</Text>
          </Pressable>
          <Text
            style={{
              flex: 1,
              fontSize: fontSizes.lg,
              fontWeight: '700',
              color: palette.textPrimary,
            }}
          >
            Staff profile
          </Text>
        </View>
      ) : null}

      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        stickyHeaderIndices={[1]}
      >
        <Staff360Header staff={header} />

        <View
          style={[
            styles.tabBarWrap,
            { backgroundColor: palette.background, paddingVertical: spacing.sm },
          ]}
        >
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            <View style={[styles.tabRow, { gap: spacing.xs }]}>
              {tabs.map((tab) => {
                const active = tab.id === activeTab;
                return (
                  <Pressable
                    key={tab.id}
                    onPress={() => onTabChange(tab.id)}
                    style={[
                      styles.tab,
                      {
                        borderRadius: radius.full,
                        backgroundColor: active ? `${colors.primary}18` : palette.surface,
                        borderColor: active ? colors.primary : palette.border,
                      },
                    ]}
                  >
                    <Text
                      style={{
                        color: active ? colors.primary : palette.textSecondary,
                        fontSize: fontSizes.sm,
                        fontWeight: active ? '700' : '500',
                      }}
                    >
                      {tab.label}
                    </Text>
                  </Pressable>
                );
              })}
            </View>
          </ScrollView>
        </View>

        <View style={{ marginTop: spacing.sm }}>{children}</View>
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  topBar: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  tabBarWrap: {},
  tabRow: { flexDirection: 'row', paddingHorizontal: 2 },
  tab: {
    borderWidth: 1,
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
});
