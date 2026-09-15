import React from 'react';
import { ScrollView, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { useAdaptiveLayout } from './useAdaptiveLayout';

export interface DockedActionLayoutProps {
  header?: React.ReactNode;
  footer?: React.ReactNode;
  children: React.ReactNode;
  style?: StyleProp<ViewStyle>;
  /**
   * Cap the filter/header block so it cannot push the list and docked
   * submit button off-screen (the tablet/phone "hanging" layout).
   */
  headerMaxFraction?: number;
}

/**
 * Column layout: optional header, flexing body (FlatList), docked footer.
 * Every ancestor must be height-bounded (`flex: 1`); this shell then
 * forces `minHeight: 0` so Android Yoga actually shrinks the list.
 */
export const DockedActionLayout: React.FC<DockedActionLayoutProps> = ({
  header,
  footer,
  children,
  style,
  headerMaxFraction = 0.42,
}) => {
  const { height } = useAdaptiveLayout();
  const maxHeader = Math.round(height * headerMaxFraction);

  return (
    <View style={[styles.root, style]}>
      {header ? (
        <ScrollView
          style={[styles.header, { maxHeight: maxHeader }]}
          contentContainerStyle={styles.headerContent}
          nestedScrollEnabled
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
          bounces={false}
        >
          {header}
        </ScrollView>
      ) : null}
      <View style={styles.body}>{children}</View>
      {footer ? <View style={styles.footer}>{footer}</View> : null}
    </View>
  );
};

const styles = StyleSheet.create({
  root: {
    flex: 1,
    minHeight: 0,
  },
  header: {
    flexGrow: 0,
    flexShrink: 1,
  },
  headerContent: {
    flexGrow: 0,
  },
  body: {
    flex: 1,
    minHeight: 0,
  },
  footer: {
    flexShrink: 0,
  },
});
