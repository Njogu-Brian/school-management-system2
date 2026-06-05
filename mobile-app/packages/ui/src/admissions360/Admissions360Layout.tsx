import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { Admissions360Header } from './Admissions360Header';
import type { Admissions360HeaderData, Admissions360TabId } from './types';

export interface Admissions360Tab {
  id: Admissions360TabId;
  label: string;
}

export interface Admissions360LayoutProps {
  header: Admissions360HeaderData;
  tabs: Admissions360Tab[];
  activeTab: Admissions360TabId;
  onTabChange: (tab: Admissions360TabId) => void;
  children: React.ReactNode;
}

export const Admissions360Layout: React.FC<Admissions360LayoutProps> = ({
  header,
  tabs,
  activeTab,
  onTabChange,
  children,
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <View style={styles.flex}>
      <View style={{ padding: spacing.md, paddingBottom: 0 }}>
        <Admissions360Header application={header} />
      </View>

      <View style={{ paddingVertical: spacing.sm }}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingHorizontal: spacing.md, gap: spacing.xs }}>
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
        </ScrollView>
      </View>

      <View style={{ flex: 1, paddingHorizontal: spacing.md }}>{children}</View>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  tab: {
    borderWidth: 1,
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
});
