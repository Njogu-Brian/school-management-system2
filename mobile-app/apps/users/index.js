// Must run before navigation. RN 0.88 removed InteractionManager and the stack crashes on launch without it.
import '../../packages/core/interactionManagerPolyfill';
// Gesture handler must be imported first.
import 'react-native-gesture-handler';
import 'react-native-reanimated';
import { registerRootComponent } from 'expo';
import App from './App';

registerRootComponent(App);
