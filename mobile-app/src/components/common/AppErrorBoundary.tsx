import React, { Component, ErrorInfo, ReactNode } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView } from 'react-native';
import { SPACING, FONT_SIZES, BORDER_RADIUS, COLORS } from '@constants/theme';

type Props = {
    children: ReactNode;
};

type State = {
    hasError: boolean;
    message: string;
};

/**
 * Catches render errors so a single bad screen does not white-screen the whole app.
 */
export class AppErrorBoundary extends Component<Props, State> {
    state: State = { hasError: false, message: '' };

    static getDerivedStateFromError(error: Error): State {
        return { hasError: true, message: error?.message || 'Unexpected error' };
    }

    componentDidCatch(error: Error, info: ErrorInfo) {
        if (__DEV__) {
            console.warn('AppErrorBoundary', error, info.componentStack);
        }
    }

    private reset = () => {
        this.setState({ hasError: false, message: '' });
    };

    render() {
        if (this.state.hasError) {
            return (
                <View style={styles.shell}>
                    <ScrollView contentContainerStyle={styles.scroll}>
                        <Text style={styles.title}>This screen had a problem</Text>
                        <Text style={styles.body}>
                            You can try again. If it keeps happening, note what you tapped last and contact support.
                        </Text>
                        {__DEV__ ? (
                            <Text style={styles.dev} selectable>
                                {this.state.message}
                            </Text>
                        ) : null}
                        <TouchableOpacity style={styles.btn} onPress={this.reset} activeOpacity={0.85}>
                            <Text style={styles.btnLabel}>Try again</Text>
                        </TouchableOpacity>
                    </ScrollView>
                </View>
            );
        }
        return this.props.children;
    }
}

const styles = StyleSheet.create({
    shell: {
        flex: 1,
        backgroundColor: COLORS.backgroundLight,
        justifyContent: 'center',
    },
    scroll: {
        padding: SPACING.xl,
        paddingTop: SPACING.xxl * 2,
    },
    title: {
        fontSize: FONT_SIZES.xl,
        fontWeight: '800',
        color: COLORS.textMainLight,
        marginBottom: SPACING.md,
    },
    body: {
        fontSize: FONT_SIZES.md,
        color: COLORS.textSubLight,
        lineHeight: 22,
        marginBottom: SPACING.lg,
    },
    dev: {
        fontSize: FONT_SIZES.xs,
        color: COLORS.error,
        marginBottom: SPACING.lg,
        fontFamily: 'monospace',
    },
    btn: {
        alignSelf: 'flex-start',
        backgroundColor: COLORS.primary,
        paddingVertical: SPACING.md,
        paddingHorizontal: SPACING.xl,
        borderRadius: BORDER_RADIUS.lg,
    },
    btnLabel: {
        color: '#fff',
        fontSize: FONT_SIZES.md,
        fontWeight: '700',
    },
});
