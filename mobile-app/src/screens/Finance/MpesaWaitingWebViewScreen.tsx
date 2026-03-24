import React, { useEffect, useRef, useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    TouchableOpacity,
    ActivityIndicator,
    Alert,
    Linking,
} from 'react-native';
import { WebView } from 'react-native-webview';
import { useTheme } from '@contexts/ThemeContext';
import { fetchMpesaTransactionStatus } from '@utils/mpesaStatus';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import Icon from 'react-native-vector-icons/MaterialIcons';

export type MpesaWaitingParams = {
    waitingUrl: string;
    statusPollUrl?: string;
};

interface Props {
    navigation: { goBack: () => void };
    route: { params: MpesaWaitingParams };
}

export const MpesaWaitingWebViewScreen: React.FC<Props> = ({ navigation, route }) => {
    const { waitingUrl, statusPollUrl } = route.params;
    const { isDark, colors } = useTheme();
    const [loading, setLoading] = useState(true);
    const pollTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const pollTries = useRef(0);
    const completedRef = useRef(false);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;

    useEffect(() => {
        if (!statusPollUrl) {
            return;
        }
        const maxTries = 90;

        const tick = async () => {
            if (completedRef.current) return;
            pollTries.current += 1;
            if (pollTries.current > maxTries) {
                return;
            }
            try {
                const s = await fetchMpesaTransactionStatus(statusPollUrl);
                const st = s.status;
                if (st === 'completed') {
                    completedRef.current = true;
                    Alert.alert('Paid', s.message || 'Payment completed.', [
                        { text: 'OK', onPress: () => navigation.goBack() },
                    ]);
                    return;
                }
                if (st === 'failed' || st === 'cancelled') {
                    completedRef.current = true;
                    Alert.alert(
                        'Payment',
                        s.failure_reason || s.message || st || 'Not completed'
                    );
                    return;
                }
            } catch {
                /* continue polling */
            }
            pollTimer.current = setTimeout(tick, 2000);
        };

        pollTimer.current = setTimeout(tick, 2500);

        return () => {
            if (pollTimer.current) {
                clearTimeout(pollTimer.current);
            }
        };
    }, [statusPollUrl, navigation]);

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: bg }]}>
            <View style={[styles.toolbar, { borderBottomColor: isDark ? colors.borderDark : BRAND.border }]}>
                <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12} accessibilityLabel="Back">
                    <Icon name="arrow-back" size={24} color={textMain} />
                </TouchableOpacity>
                <Text style={[styles.toolbarTitle, { color: textMain }]} numberOfLines={1}>
                    M-Pesa status
                </Text>
                <TouchableOpacity
                    onPress={() => Linking.openURL(waitingUrl)}
                    hitSlop={12}
                    accessibilityLabel="Open in browser"
                >
                    <Icon name="open-in-new" size={22} color={BRAND.primary} />
                </TouchableOpacity>
            </View>

            {loading && (
                <View style={styles.loadingBar}>
                    <ActivityIndicator color={BRAND.primary} />
                </View>
            )}

            <WebView
                source={{ uri: waitingUrl }}
                style={styles.webview}
                onLoadEnd={() => setLoading(false)}
                onError={() => setLoading(false)}
                startInLoadingState
                originWhitelist={['*']}
                setSupportMultipleWindows={false}
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    toolbar: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        borderBottomWidth: StyleSheet.hairlineWidth,
    },
    toolbarTitle: {
        flex: 1,
        textAlign: 'center',
        fontSize: FONT_SIZES.lg,
        fontWeight: '600',
        marginHorizontal: SPACING.sm,
    },
    loadingBar: {
        paddingVertical: SPACING.xs,
    },
    webview: {
        flex: 1,
        borderRadius: RADIUS.card,
    },
});
