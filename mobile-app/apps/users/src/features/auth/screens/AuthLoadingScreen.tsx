import { useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';

export const AuthLoadingScreen: React.FC = () => {
  const { palette, colors } = useTheme();
  return (
    <View style={[styles.container, { backgroundColor: palette.background }]}>
      <View style={[styles.logo, { backgroundColor: colors.primary }]}>
        <Ionicons name="people" size={32} color={colors.white} />
      </View>
      <ActivityIndicator color={colors.primary} style={styles.spinner} />
    </View>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  logo: {
    width: 64,
    height: 64,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  spinner: { marginTop: 24 },
});
