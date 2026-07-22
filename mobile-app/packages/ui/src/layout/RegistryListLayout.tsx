import React from 'react';
import { FlatList, StyleSheet, View, type FlatListProps } from 'react-native';
import { FilterBottomSheet } from '../filters/FilterBottomSheet';
import { FilterTriggerButton } from '../filters/FilterTriggerButton';
import { useTheme } from '../theme/ThemeContext';
import { FLOATING_TAB_BAR_CLEARANCE } from './PremiumTabBar';

export interface RegistryListLayoutProps<T> extends Omit<
  FlatListProps<T>,
  'ListHeaderComponent' | 'contentContainerStyle'
> {
  hero?: React.ReactNode;
  searchBar: React.ReactNode;
  activeFilterCount?: number;
  filtersOpen?: boolean;
  onOpenFilters?: () => void;
  onCloseFilters?: () => void;
  onApplyFilters?: () => void;
  onClearFilters?: () => void;
  filterSheetTitle?: string;
  filterContent?: React.ReactNode;
  showFilterTrigger?: boolean;
  stickyPaddingHorizontal?: number;
  contentContainerStyle?: FlatListProps<T>['contentContainerStyle'];
}

/**
 * Registry list shell: sticky search + filter trigger, scrollable hero, list body.
 * Gmail-style search that stays visible while the hero scrolls away.
 */
export function RegistryListLayout<T>({
  hero,
  searchBar,
  activeFilterCount = 0,
  filtersOpen = false,
  onOpenFilters,
  onCloseFilters,
  onApplyFilters,
  onClearFilters,
  filterSheetTitle,
  filterContent,
  showFilterTrigger = true,
  stickyPaddingHorizontal,
  contentContainerStyle,
  ...flatListProps
}: RegistryListLayoutProps<T>) {
  const { palette, spacing } = useTheme();
  const horizontal = stickyPaddingHorizontal ?? spacing.md;

  return (
    <View style={styles.flex}>
      <View
        style={[
          styles.sticky,
          {
            backgroundColor: palette.surface,
            borderBottomColor: palette.borderSubtle,
            paddingHorizontal: horizontal,
            paddingTop: spacing.sm,
            paddingBottom: spacing.sm,
            gap: spacing.sm,
          },
        ]}
      >
        {searchBar}
        {showFilterTrigger && onOpenFilters ? (
          <FilterTriggerButton activeCount={activeFilterCount} onPress={onOpenFilters} />
        ) : null}
      </View>

      <FlatList
        {...flatListProps}
        contentContainerStyle={[
          { paddingHorizontal: horizontal, paddingBottom: FLOATING_TAB_BAR_CLEARANCE },
          contentContainerStyle,
        ]}
        ListHeaderComponent={
          hero ? <View style={{ marginBottom: spacing.sm }}>{hero}</View> : undefined
        }
      />

      {filterContent && onCloseFilters && onApplyFilters && onClearFilters ? (
        <FilterBottomSheet
          visible={filtersOpen}
          title={filterSheetTitle}
          onClose={onCloseFilters}
          onApply={onApplyFilters}
          onClear={onClearFilters}
        >
          {filterContent}
        </FilterBottomSheet>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  sticky: {
    zIndex: 2,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
});
