import React from 'react';
import { AppPermissionGate, type AppPermissionGateProps } from './AppPermissionGate';

/** Shorthand for `AppPermissionGate` (deliverable name: Can). */
export const Can: React.FC<AppPermissionGateProps> = (props) => <AppPermissionGate {...props} />;
