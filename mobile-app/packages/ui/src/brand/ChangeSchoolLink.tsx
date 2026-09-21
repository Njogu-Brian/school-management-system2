import { isCombinedApp, REQUIRE_SCHOOL_CODE, useSchoolOptional } from '@erp/core';
import React from 'react';
import { Pressable, Text } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

type Props = {
  /** Light text for the dark login sheet. */
  onDark?: boolean;
  label?: string;
};

/**
 * Lets the user pick a different school code. Hidden on Royal Kings binaries
 * that silently bind to RKS001.
 */
export const ChangeSchoolLink: React.FC<Props> = ({
  onDark = false,
  label = 'Change school',
}) => {
  const school = useSchoolOptional();
  const { colors, spacing, typography } = useTheme();

  if (!school) return null;
  if (!REQUIRE_SCHOOL_CODE && !isCombinedApp()) return null;

  return (
    <Pressable
      onPress={() => void school.clearSchool()}
      accessibilityRole="button"
      accessibilityLabel="Change school"
      style={{ marginTop: spacing.md, alignItems: 'center', paddingVertical: spacing.sm }}
    >
      <Text
        style={{
          color: onDark ? 'rgba(255,255,255,0.75)' : colors.primary,
          fontWeight: '600',
          fontSize: typography.caption.fontSize,
        }}
      >
        {school.school ? `${label} (${school.school.code})` : label}
      </Text>
    </Pressable>
  );
};
