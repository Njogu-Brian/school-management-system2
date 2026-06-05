import type { ApplicationDetail } from '@erp/core';
import { ApplicationFieldSection } from '@erp/ui';
import React from 'react';
import { ScrollView } from 'react-native';

function parentRows(side: ApplicationDetail['father']) {
  return [
    { label: 'Name', value: side.name },
    { label: 'Phone', value: side.phone },
    { label: 'Email', value: side.email },
    { label: 'ID number', value: side.id_number },
    ...(side.relationship ? [{ label: 'Relationship', value: side.relationship }] : []),
  ];
}

export interface ParentsTabProps {
  application: ApplicationDetail;
}

export const ParentsTab: React.FC<ParentsTabProps> = ({ application }) => (
  <ScrollView showsVerticalScrollIndicator={false}>
    <ApplicationFieldSection title="Father" rows={parentRows(application.father)} />
    <ApplicationFieldSection title="Mother" rows={parentRows(application.mother)} />
    <ApplicationFieldSection title="Guardian" rows={parentRows(application.guardian)} />
    <ApplicationFieldSection
      title="Family"
      rows={[{ label: 'Marital status', value: application.maritalStatus }]}
    />
  </ScrollView>
);
