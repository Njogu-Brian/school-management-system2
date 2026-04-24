import React from 'react';
import {
    KeyboardAvoidingView,
    Platform,
    ScrollView,
    ScrollViewProps,
    StyleProp,
    StyleSheet,
    View,
    ViewStyle,
} from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '@contexts/ThemeContext';

interface ScreenContainerProps {
    children: React.ReactNode;
    scroll?: boolean;
    style?: StyleProp<ViewStyle>;
    contentContainerStyle?: StyleProp<ViewStyle>;
    scrollProps?: ScrollViewProps;
    edges?: Array<'top' | 'bottom' | 'left' | 'right'>;
    keyboardVerticalOffset?: number;
}

/**
 * Wraps screens with a consistent KeyboardAvoidingView + ScrollView so that
 * TextInputs are not obstructed by the on-screen keyboard. Pass `scroll={false}`
 * for screens that shouldn't scroll (e.g. list screens with FlatList).
 */
export const ScreenContainer: React.FC<ScreenContainerProps> = ({
    children,
    scroll = true,
    style,
    contentContainerStyle,
    scrollProps,
    edges = ['top', 'bottom'],
    keyboardVerticalOffset,
}) => {
    const { isDark, colors } = useTheme();
    const insets = useSafeAreaInsets();

    const bg = isDark ? colors.backgroundDark : colors.backgroundLight;

    const body = scroll ? (
        <ScrollView
            style={styles.scroll}
            keyboardShouldPersistTaps="handled"
            contentContainerStyle={[styles.scrollContent, contentContainerStyle]}
            showsVerticalScrollIndicator={false}
            {...scrollProps}
        >
            {children}
        </ScrollView>
    ) : (
        <View style={[styles.scroll, contentContainerStyle]}>{children}</View>
    );

    return (
        <SafeAreaView edges={edges} style={[{ flex: 1, backgroundColor: bg }, style]}>
            <KeyboardAvoidingView
                style={styles.flex}
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
                keyboardVerticalOffset={keyboardVerticalOffset ?? insets.top}
            >
                {body}
            </KeyboardAvoidingView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    flex: { flex: 1 },
    scroll: { flex: 1 },
    scrollContent: { flexGrow: 1 },
});
