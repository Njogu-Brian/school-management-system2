// Must run before navigation. RN 0.88 removed InteractionManager and the drawer crashes on launch without it.
import '../../packages/core/interactionManagerPolyfill';
// Gesture handler must be imported first (required by @react-navigation/drawer).
import 'react-native-gesture-handler';
import 'react-native-reanimated';
import { registerRootComponent } from 'expo';
import App from './App';

registerRootComponent(App);
