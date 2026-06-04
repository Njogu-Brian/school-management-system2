import type { LinkingOptions } from '@react-navigation/native';
import type { DrawerParamList } from './types';

/**
 * Deep-link configuration foundation (build plan §5.5). Notification/search payloads
 * resolve to these paths in a later batch; for now it wires the route map so the shell
 * is link-ready.
 */
export const linking: LinkingOptions<DrawerParamList> = {
  prefixes: ['schoolerpadmin://', 'https://admin.schoolerp.app'],
  config: {
    screens: {
      Workspace: {
        screens: {
          Dashboard: 'dashboard',
          Students: 'students',
          Finance: 'finance',
          People: {
            path: 'people',
            screens: {
              StaffRegistry: '',
              StaffDetail: ':staffId',
            },
          },
        },
      },
      Admissions: 'admissions',
      Academics: 'academics',
      Operations: 'operations',
      Communication: 'communication',
      Reports: 'reports',
      Settings: 'settings',
    },
  },
};
