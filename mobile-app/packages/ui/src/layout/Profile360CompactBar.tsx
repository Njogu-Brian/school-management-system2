import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface Profile360CompactBarProps {
  title: string;
  subtitle?: string;
}

export const Profile360CompactBar: React.FC<Profile360CompactBarProps> = ({ title, subtitle }) => {
  const { palette, typography } = useTheme();

  return (
    <View style={styles.row}>
      <Text
        numberOfLines={1}
        style={{
          flex: 1,
          color: palette.textPrimary,
          fontSize: typography.body.fontSize,
          fontWeight: '700',
        }}
      >
        {title}
      </Text>
      {subtitle ? (
        <Text
          numberOfLines={1}
          style={{
            color: palette.textMuted,
            fontSize: typography.caption.fontSize,
            marginLeft: 8,
            maxWidth: '45%',
          }}
        >
          {subtitle}
        </Text>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center' },
});
