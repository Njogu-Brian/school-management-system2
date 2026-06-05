import type { ApplicationDetail } from '@erp/core';
import { ApplicationTimeline } from '@erp/ui';
import React, { useMemo } from 'react';
import { ScrollView } from 'react-native';

export interface TimelineTabProps {
  application: ApplicationDetail;
}

export const TimelineTab: React.FC<TimelineTabProps> = ({ application }) => {
  const items = useMemo(
    () =>
      application.timeline.map((e) => ({
        id: e.id,
        title: e.title,
        description: e.description,
        occurredOn: e.occurred_on,
      })),
    [application.timeline],
  );

  return (
    <ScrollView showsVerticalScrollIndicator={false}>
      <ApplicationTimeline items={items} />
    </ScrollView>
  );
};
