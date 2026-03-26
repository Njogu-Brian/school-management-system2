import React, { createContext, useContext } from 'react';

const AdminBrandedContext = createContext(false);

export const AdminBrandedProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => (
    <AdminBrandedContext.Provider value={true}>{children}</AdminBrandedContext.Provider>
);

export function useIsAdminBrandedApp(): boolean {
    return useContext(AdminBrandedContext);
}
