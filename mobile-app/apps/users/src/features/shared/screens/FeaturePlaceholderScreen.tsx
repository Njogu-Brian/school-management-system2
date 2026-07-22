import { AcademicScreenHeader, EmptyState, ScreenContainer, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React from 'react';
import { Text } from 'react-native';

/** Placeholder hub until a deeper feature is ported. */
export const FeaturePlaceholderScreen: React.FC<{
  title: string;
  message: string;
}> = ({ title, message }) => {
  const navigation = useNavigation();
  const { palette, spacing, typography } = useTheme();
  const canGoBack = navigation.canGoBack();

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title={title}
        onBack={canGoBack ? () => navigation.goBack() : undefined}
      />
      <EmptyState title={title} message={message} icon="construct-outline" />
      <Text
        style={{
          color: palette.textMuted,
          fontSize: typography.caption.fontSize,
          textAlign: 'center',
          marginTop: spacing.md,
        }}
      >
        Shell is live — deeper capture UI will land next.
      </Text>
    </ScreenContainer>
  );
};
