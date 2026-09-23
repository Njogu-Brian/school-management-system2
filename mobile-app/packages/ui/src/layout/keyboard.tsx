import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
} from 'react';
import {
  Keyboard,
  Platform,
  ScrollView,
  TextInput,
  type HostInstance,
  type MeasureInWindowOnSuccessCallback,
  type MeasureLayoutOnSuccessCallback,
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

type MeasurableHost = HostInstance & {
  measureLayout: (
    relativeToNativeNode: HostInstance,
    onSuccess: MeasureLayoutOnSuccessCallback,
    onFail?: () => void,
  ) => void;
  measureInWindow: (callback: MeasureInWindowOnSuccessCallback) => void;
};

type ScrollViewNativeHelpers = ScrollView & {
  getInnerViewRef?: () => HostInstance | null;
  getNativeScrollRef?: () => MeasurableHost | null;
};

function asMeasurable(node: HostInstance | null | undefined): MeasurableHost | null {
  if (!node) return null;
  const candidate = node as MeasurableHost;
  if (typeof candidate.measureLayout !== 'function' || typeof candidate.measureInWindow !== 'function') {
    return null;
  }
  return candidate;
}

/**
 * Fabric/RN 0.7x+ requires measureLayout's relative target to be a native host
 * instance (ReactNativeElement), not a findNodeHandle numeric id. Use the
 * ScrollView inner content view so the returned Y matches scrollTo content offset.
 */
function getScrollContentMeasureTarget(scroll: ScrollView): MeasurableHost | null {
  const helpers = scroll as ScrollViewNativeHelpers;
  return asMeasurable(helpers.getInnerViewRef?.());
}

function scrollFocusedInputViaWindow(
  scroll: ScrollView,
  input: MeasurableHost,
  extraGap: number,
): void {
  const viewport = asMeasurable((scroll as ScrollViewNativeHelpers).getNativeScrollRef?.());
  if (!viewport) {
    requestAnimationFrame(() => scroll.scrollToEnd({ animated: true }));
    return;
  }

  input.measureInWindow((_ix, iy, _iw, ih) => {
    viewport.measureInWindow((_sx, sy, _sw, sh) => {
      const visibleTop = sy + extraGap;
      const visibleBottom = sy + sh - extraGap;
      const inputTop = iy;
      const inputBottom = iy + ih;

      if (inputTop >= visibleTop && inputBottom <= visibleBottom) {
        return;
      }

      // Without a tracked contentOffset, nudge toward the focused field.
      // Prefer the measureLayout path above when the inner view ref is available.
      scroll.scrollToEnd({ animated: true });
    });
  });
}

export function scrollFocusedInputIntoView(scroll: ScrollView | null, extraGap = 28): void {
  if (!scroll) return;

  const input = asMeasurable(TextInput.State.currentlyFocusedInput?.() as HostInstance | null | undefined);
  const relativeTo = getScrollContentMeasureTarget(scroll);

  if (!input || !relativeTo) {
    if (input) {
      scrollFocusedInputViaWindow(scroll, input, extraGap);
      return;
    }
    requestAnimationFrame(() => scroll.scrollToEnd({ animated: true }));
    return;
  }

  try {
    // Pass the native host ref directly — never findNodeHandle (numeric ids fail on Fabric).
    input.measureLayout(
      relativeTo,
      (_x, y) => {
        scroll.scrollTo({ y: Math.max(0, y - extraGap), animated: true });
      },
      () => {
        scrollFocusedInputViaWindow(scroll, input, extraGap);
      },
    );
  } catch {
    scrollFocusedInputViaWindow(scroll, input, extraGap);
  }
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
