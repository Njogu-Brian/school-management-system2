import { getNavArea } from '@erp/core';
import { PlaceholderScreen } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React from 'react';

const area = getNavArea('settings');

export const SettingsScreen: React.FC = () => (
  <PlaceholderScreen
    title={area.label}
    description={area.description}
    icon={area.icon as keyof typeof Ionicons.glyphMap}
    sections={area.sections}
  />
);
