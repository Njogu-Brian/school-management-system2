import React, { useState, useEffect } from 'react';
import {
    Modal,
    View,
    Text,
    StyleSheet,
    TouchableOpacity,
    Alert,
    ActivityIndicator,
    Linking,
    ScrollView,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Input } from '@components/common/Input';
import { Button } from '@components/common/Button';
import { financeApi } from '@api/finance.api';
import { fetchMpesaTransactionStatus, isTrustedMpesaUrl } from '@utils/mpesaStatus';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import Icon from 'react-native-vector-icons/MaterialIcons';

type NavLike = { navigate: (name: string, params?: Record<string, unknown>) => void };

interface MpesaPromptModalProps {
    visible: boolean;
    studentId: number;
    defaultAmount?: string;
    onClose: () => void;
    /** When set, successful STK opens in-app WebView + background status polling. */
    navigation?: NavLike;
}

export const MpesaPromptModal: React.FC<MpesaPromptModalProps> = ({
    visible,
    studentId,
    defaultAmount = '',
    onClose,
    navigation,
}) => {
    const { isDark, colors } = useTheme();
    const [phone, setPhone] = useState('');
    const [amount, setAmount] = useState(defaultAmount);
    const [notes, setNotes] = useState('');
    const [loading, setLoading] = useState(false);
    const pollTimerRef = React.useRef<ReturnType<typeof setTimeout> | null>(null);
    const pollActiveRef = React.useRef(false);

    useEffect(() => {
        if (visible && defaultAmount !== undefined) {
            setAmount(defaultAmount);
        }
    }, [visible, defaultAmount]);

    useEffect(() => {
        if (!visible && pollTimerRef.current) {
            clearTimeout(pollTimerRef.current);
            pollTimerRef.current = null;
            pollActiveRef.current = false;
        }
    }, [visible]);

    useEffect(() => {
        return () => {
            if (pollTimerRef.current) {
                clearTimeout(pollTimerRef.current);
                pollTimerRef.current = null;
            }
            pollActiveRef.current = false;
        };
    }, []);

    const textMain = isDark ? colors.textMainDark : colors.textMainLight;
    const textSub = isDark ? colors.textSubDark : colors.textSubLight;
    const surface = isDark ? colors.surfaceDark : BRAND.surface;

    const handleSubmit = async () => {
        const amt = parseFloat(amount);
        if (!phone.trim()) {
            Alert.alert('Validation', 'Enter parent M-Pesa phone number.');
            return;
        }
        if (!amt || amt < 1) {
            Alert.alert('Validation', 'Enter amount (at least 1 KES).');
            return;
        }
        setLoading(true);
        try {
            const res = await financeApi.mpesaPrompt(studentId, {
                phone_number: phone.trim(),
                amount: amt,
                notes: notes.trim() || undefined,
            });
            if (!res.success || !res.data) {
                Alert.alert('M-Pesa', (res as { message?: string }).message || 'Could not send STK prompt.');
                return;
            }
            const { waiting_url, status_poll_url } = res.data;
            const pollUrl = status_poll_url;

            setPhone('');
            setNotes('');
            onClose();

            if (navigation && waiting_url && isTrustedMpesaUrl(waiting_url)) {
                navigation.navigate('MpesaWaitingWeb', {
                    waitingUrl: waiting_url,
                    statusPollUrl: pollUrl && isTrustedMpesaUrl(pollUrl) ? pollUrl : undefined,
                });
                return;
            }

            Alert.alert(
                'STK sent',
                'Ask the parent to enter their M-Pesa PIN. You can open the status page or wait here for completion.',
                [
                    { text: 'OK', style: 'cancel' },
                    ...(waiting_url && isTrustedMpesaUrl(waiting_url)
                        ? [{ text: 'Open status page', onPress: () => Linking.openURL(waiting_url) }]
                        : []),
                    ...(pollUrl && isTrustedMpesaUrl(pollUrl)
                        ? [
                              {
                                  text: 'Wait for result',
                                  onPress: () => runPoll(pollUrl),
                              },
                          ]
                        : []),
                ]
            );
        } catch (e: any) {
            Alert.alert('Error', e.message || 'Request failed');
        } finally {
            setLoading(false);
        }
    };

    const runPoll = (pollUrl: string) => {
        if (!isTrustedMpesaUrl(pollUrl)) {
            Alert.alert('M-Pesa', 'Received an untrusted status URL. Please retry from the dashboard.');
            return;
        }

        if (pollTimerRef.current) {
            clearTimeout(pollTimerRef.current);
            pollTimerRef.current = null;
        }

        pollActiveRef.current = true;
        let tries = 0;
        const maxTries = 45;
        const tick = async () => {
            if (!pollActiveRef.current) return;
            tries += 1;
            try {
                const s = await fetchMpesaTransactionStatus(pollUrl);
                if (s.status === 'completed') {
                    Alert.alert('Paid', s.message || 'Payment completed.');
                    return;
                }
                if (s.status === 'failed' || s.status === 'cancelled') {
                    Alert.alert('Payment', s.failure_reason || s.message || s.status || 'Not completed');
                    return;
                }
            } catch {
                /* keep polling */
            }
            if (!pollActiveRef.current) return;
            if (tries < maxTries) {
                pollTimerRef.current = setTimeout(tick, 2000);
            } else {
                pollActiveRef.current = false;
                Alert.alert('Timeout', 'Still waiting on M-Pesa. Check the finance dashboard or open the status page.');
            }
        };
        tick();
    };

    return (
        <Modal visible={visible} animationType="slide" transparent onRequestClose={onClose}>
            <View style={styles.overlay}>
                <View style={[styles.box, { backgroundColor: surface }]}>
                    <View style={styles.titleRow}>
                        <Text style={[styles.title, { color: textMain }]}>Prompt parent (M-Pesa)</Text>
                        <TouchableOpacity onPress={onClose} hitSlop={12}>
                            <Icon name="close" size={24} color={textSub} />
                        </TouchableOpacity>
                    </View>
                    <Text style={[styles.hint, { color: textSub }]}>
                        Sends an STK push to the number below (same as web finance).
                    </Text>
                    <ScrollView keyboardShouldPersistTaps="handled" style={styles.form}>
                        <Input
                            label="M-Pesa phone"
                            value={phone}
                            onChangeText={setPhone}
                            placeholder="0712345678"
                            keyboardType="phone-pad"
                        />
                        <Input
                            label="Amount (KES)"
                            value={amount}
                            onChangeText={setAmount}
                            placeholder="5000"
                            keyboardType="decimal-pad"
                        />
                        <Input label="Notes (optional)" value={notes} onChangeText={setNotes} placeholder="Invoice ref…" />
                        {loading ? (
                            <ActivityIndicator color={BRAND.primary} style={{ marginVertical: SPACING.md }} />
                        ) : (
                            <Button title="Send STK prompt" onPress={handleSubmit} style={{ marginTop: SPACING.md }} />
                        )}
                    </ScrollView>
                </View>
            </View>
        </Modal>
    );
};

const styles = StyleSheet.create({
    overlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.45)',
        justifyContent: 'flex-end',
    },
    box: {
        borderTopLeftRadius: RADIUS.card,
        borderTopRightRadius: RADIUS.card,
        padding: SPACING.lg,
        maxHeight: '85%',
    },
    titleRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: SPACING.sm,
    },
    title: { fontSize: FONT_SIZES.lg, fontWeight: '700' },
    hint: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.md },
    form: { maxHeight: 400 },
});
