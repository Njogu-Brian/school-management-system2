import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { COLORS } from '../theme/tokens';

interface AppErrorBoundaryProps {
  children: React.ReactNode;
}

interface AppErrorBoundaryState {
  hasError: boolean;
  message?: string;
}

/**
 * Top-level render guard. Catches render-time errors so a single broken screen never
 * crashes the whole shell. Crash reporting (Sentry) is wired in a later batch
 * (build plan §1.3); for now it offers a local reset.
 *
 * Uses raw tokens (not useTheme) so it stays functional even if a provider above failed.
 */
export class AppErrorBoundary extends React.Component<
  AppErrorBoundaryProps,
  AppErrorBoundaryState
> {
  constructor(props: AppErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(error: Error): AppErrorBoundaryState {
    return { hasError: true, message: error.message };
  }

  override componentDidCatch(error: Error): void {
    // eslint-disable-next-line no-console
    console.error('[AppErrorBoundary]', error);
  }

  private handleReset = (): void => {
    this.setState({ hasError: false, message: undefined });
  };

  override render(): React.ReactNode {
    if (!this.state.hasError) {
      return this.props.children;
    }

    return (
      <View style={styles.wrap}>
        <Ionicons name="warning-outline" size={44} color={COLORS.warning} />
        <Text style={styles.title}>Something went wrong</Text>
        <Text style={styles.message}>{this.state.message ?? 'An unexpected error occurred.'}</Text>
        <Pressable
          accessibilityRole="button"
          onPress={this.handleReset}
          style={styles.button}
        >
          <Text style={styles.buttonText}>Reload</Text>
        </Pressable>
      </View>
    );
  }
}

const styles = StyleSheet.create({
  wrap: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
    backgroundColor: COLORS.backgroundLight,
  },
  title: {
    marginTop: 12,
    fontSize: 20,
    fontWeight: '700',
    color: COLORS.textMainLight,
  },
  message: {
    marginTop: 8,
    fontSize: 14,
    textAlign: 'center',
    color: COLORS.textSubLight,
    maxWidth: 320,
  },
  button: {
    marginTop: 20,
    backgroundColor: COLORS.primary,
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 12,
  },
  buttonText: { color: COLORS.white, fontWeight: '700', fontSize: 14 },
});
