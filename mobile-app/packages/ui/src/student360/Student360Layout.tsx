import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { Student360Header } from './Student360Header';
import type { Student360HeaderData, Student360TabId } from './types';

export interface Student360Tab {
  id: Student360TabId;
  label: string;
}

export interface Student360LayoutProps {
  header: Student360HeaderData;
  tabs: Student360Tab[];
  activeTab: Student360TabId;
  onTabChange: (tab: Student360TabId) => void;
  children: React.ReactNode;
}

export const Student360Layout: React.FC<Student360LayoutProps> = ({
  header,
  tabs,
  activeTab,
  onTabChange,
  children,
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <View style={styles.flex}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        stickyHeaderIndices={[1]}
      >
        <Student360Header student={header} />

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
  tabBarWrap: {},
  tabRow: { flexDirection: 'row', paddingHorizontal: 2 },
  tab: {
    borderWidth: 1,
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
});
