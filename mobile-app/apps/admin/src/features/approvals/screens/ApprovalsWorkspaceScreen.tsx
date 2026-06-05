import type { ApprovalItem } from '@erp/core';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback } from 'react';
import type { ApprovalsStackParamList } from '../../../navigation/approvalsStackTypes';
import { ApprovalsInbox } from '../components/ApprovalsInbox';
import { useCanViewApprovals } from '../hooks/useCanViewApprovals';

type Props = StackScreenProps<ApprovalsStackParamList, 'ApprovalsHome'>;

export const ApprovalsWorkspaceScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCanViewApprovals();

  const onOpenDetail = useCallback(
    (item: ApprovalItem) => {
      navigation.navigate('ApprovalDetail', { id: item.id, item });
    },
    [navigation],
  );

  return <ApprovalsInbox canView={canView} onOpenDetail={onOpenDetail} />;
};
