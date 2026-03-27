import { AppRegistry } from 'react-native';
import App from './src/App';

// Expo bare Android entrypoint expects "main".
AppRegistry.registerComponent('main', () => App);
// Backward-compatible registration for older native builds.
AppRegistry.registerComponent('SchoolERP', () => App);
