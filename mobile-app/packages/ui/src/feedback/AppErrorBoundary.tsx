import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { COLORS } from '../theme/tokens';

interface AppErrorBoundaryProps {
  children: React.ReactNode;
  /** users | admin — included in crash reports */
  appName?: 'users' | 'admin';
}

interface AppErrorBoundaryState {
  hasError: boolean;
  message?: string;
}

type IssueReporter = (payload: {
  app?: 'users' | 'admin';
  platform?: string;
  app_version?: string;
  message: string;
  stack?: string;
  component_stack?: string;
  extra?: Record<string, unknown>;
}) => Promise<unknown>;

let issueReporter: IssueReporter | null = null;

/** Register once from App.tsx so the boundary can POST without importing api client at module load. */
export function registerAppIssueReporter(reporter: IssueReporter | null): void {
  issueReporter = reporter;
}

/**
 * Top-level render guard. Reports crashes to POST /app-issues when a reporter is registered.
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

  override componentDidCatch(error: Error, info: React.ErrorInfo): void {
    // eslint-disable-next-line no-console
    console.error('[AppErrorBoundary]', error, info?.componentStack);
    if (!issueReporter) return;
    void issueReporter({
      app: this.props.appName ?? 'users',
      platform: Platform.OS,
      message: error.message || 'Unknown render error',
      stack: error.stack,
      component_stack: info?.componentStack ?? undefined,
      extra: { name: error.name },
    }).catch(() => {
      /* never block UI */
    });
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
        <Pressable accessibilityRole="button" onPress={this.handleReset} style={styles.button}>
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
    textAlign: 'center',
    color: COLORS.textSubLight,
  },
  button: {
    marginTop: 20,
    backgroundColor: COLORS.primary,
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 12,
  },
  buttonText: { color: '#fff', fontWeight: '700' },
});
