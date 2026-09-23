/**
 * React Native 0.88 removed InteractionManager.
 * In dev, reading it throws and kills the session. In release it is undefined,
 * so the drawer and stack crash as soon as a screen transition starts.
 * Navigation still calls it, so restore a no-op before those modules load.
 */
const ReactNative = require('react-native');

const noop = () => {};

const interactionManager = {
  createInteractionHandle() {
    return 1;
  },
  clearInteractionHandle: noop,
  runAfterInteractions(task) {
    const result = typeof task === 'function' ? task() : undefined;
    const promise = Promise.resolve(result);
    return {
      then: promise.then.bind(promise),
      done: noop,
      cancel: noop,
    };
  },
  setDeadline: noop,
  Events: {
    interactionStart: 'interactionStart',
    interactionComplete: 'interactionComplete',
  },
  addListener() {
    return { remove: noop };
  },
};

Object.defineProperty(ReactNative, 'InteractionManager', {
  configurable: true,
  enumerable: true,
  get() {
    return interactionManager;
  },
});
