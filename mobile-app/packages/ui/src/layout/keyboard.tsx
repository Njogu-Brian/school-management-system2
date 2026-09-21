import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
} from 'react';
import {
  findNodeHandle,
  Keyboard,
  Platform,
  ScrollView,
  TextInput,
} from 'react-native';

type EnsureVisible = () => void;

const KeyboardScrollContext = createContext<EnsureVisible | null>(null);

/** Call from a focused field so the nearest keyboard-aware scroll view brings it into view. */
export function useEnsureInputVisible(): EnsureVisible | null {
  return useContext(KeyboardScrollContext);
}

/** Live keyboard height (0 when hidden). */
export function useKeyboardHeight(): number {
  const [height, setHeight] = useState(0);
  useEffect(() => {
    const showEvt = Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow';
    const hideEvt = Platform.OS === 'ios' ? 'keyboardWillHide' : 'keyboardDidHide';
    const show = Keyboard.addListener(showEvt, (e) => setHeight(e.endCoordinates.height));
    const hide = Keyboard.addListener(hideEvt, () => setHeight(0));
    return () => {
      show.remove();
      hide.remove();
    };
  }, []);
  return height;
}

export function scrollFocusedInputIntoView(scroll: ScrollView | null, extraGap = 28): void {
  if (!scroll) return;
  const input = TextInput.State.currentlyFocusedInput?.();
  const scrollHandle = findNodeHandle(scroll);
  if (!input || !scrollHandle) {
    requestAnimationFrame(() => scroll.scrollToEnd({ animated: true }));
    return;
  }
  input.measureLayout(
    scrollHandle,
    (_x, y, _w, _h) => {
      scroll.scrollTo({ y: Math.max(0, y - extraGap), animated: true });
    },
    () => {
      scroll.scrollToEnd({ animated: true });
    },
  );
}

export const KeyboardScrollProvider: React.FC<{
  children: React.ReactNode;
  scrollRef: React.RefObject<ScrollView | null>;
}> = ({ children, scrollRef }) => {
  const ensureVisible = useCallback(() => {
    requestAnimationFrame(() => scrollFocusedInputIntoView(scrollRef.current));
  }, [scrollRef]);

  useEffect(() => {
    const evt = Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow';
    const sub = Keyboard.addListener(evt, () => ensureVisible());
    return () => sub.remove();
  }, [ensureVisible]);

  return (
    <KeyboardScrollContext.Provider value={ensureVisible}>{children}</KeyboardScrollContext.Provider>
  );
};
