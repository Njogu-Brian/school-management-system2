import { QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { getAppQueryClient } from './queryClient';

export interface QueryProviderProps {
  children: React.ReactNode;
  client?: ReturnType<typeof getAppQueryClient>;
}

export const QueryProvider: React.FC<QueryProviderProps> = ({ children, client }) => {
  const queryClient = client ?? getAppQueryClient();
  return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
};
