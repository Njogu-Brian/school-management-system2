import React, { useEffect, useRef } from 'react';
import { Animated, StyleSheet, View, type ViewStyle } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface SkeletonLoaderProps {
  width?: number | `${number}%`;
  height?: number;
  style?: ViewStyle;
  borderRadius?: number;
}

export const SkeletonLoader: React.FC<SkeletonLoaderProps> = ({
  width = '100%',
  height = 16,
  style,
  borderRadius = 6,
}) => {
  const { palette } = useTheme();
  const opacity = useRef(new Animated.Value(0.4)).current;

  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, { toValue: 1, duration: 700, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 0.4, duration: 700, useNativeDriver: true }),
      ]),
    );
    loop.start();
    return () => loop.stop();
  }, [opacity]);

  return (
    <Animated.View
      style={[
        styles.block,
        {
          width,
          height,
          borderRadius,
          backgroundColor: palette.border,
          opacity,
        },
        style,
      ]}
    />
  );
};

export const SkeletonCard: React.FC = () => {
  const { spacing } = useTheme();
  return (
    <View style={{ marginBottom: spacing.sm }}>
      <SkeletonLoader height={20} width="60%" />
      <SkeletonLoader height={14} width="90%" style={{ marginTop: 8 }} />
      <SkeletonLoader height={14} width="70%" style={{ marginTop: 6 }} />
    </View>
  );
};

const styles = StyleSheet.create({
  block: {},
});
