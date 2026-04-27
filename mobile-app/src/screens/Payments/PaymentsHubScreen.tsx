import React, { useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, TouchableOpacity } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import { PaymentsListScreen } from '@screens/Finance/PaymentsListScreen';
import { TransactionsListScreen } from './TransactionsListScreen';

interface Props {
    navigation: { navigate: (name: string, params?: object) => void };
}

type Segment = 'payments' | 'transactions';

export const PaymentsHubScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [segment, setSegment] = useState<Segment>('payments');

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
            <View style={styles.header}>
                <Text style={[styles.title, { color: textMain }]}>Payments</Text>
                <Text style={[styles.sub, { color: textSub }]}>
                    Receipts and bank / M-Pesa paybill lines. Use the portal for bulk confirm and auto-assign.
                </Text>
            </View>

            <View style={styles.segmentRow}>
                <TouchableOpacity
                    style={[
                        styles.segment,
                        {
                            backgroundColor:
                                segment === 'payments' ? colors.primary + '22' : isDark ? colors.surfaceDark : BRAND.surface,
                            borderColor: segment === 'payments' ? colors.primary : isDark ? colors.borderDark : BRAND.border,
                        },
                    ]}
                    onPress={() => setSegment('payments')}
                >
                    <Text
                        style={{
                            fontWeight: '700',
                            fontSize: FONT_SIZES.sm,
                            color: segment === 'payments' ? colors.primary : textSub,
                        }}
                    >
                        Recent payments
                    </Text>
                </TouchableOpacity>
                <TouchableOpacity
                    style={[
                        styles.segment,
                        {
                            backgroundColor:
                                segment === 'transactions'
                                    ? colors.primary + '22'
                                    : isDark
                                      ? colors.surfaceDark
                                      : BRAND.surface,
                            borderColor:
                                segment === 'transactions' ? colors.primary : isDark ? colors.borderDark : BRAND.border,
                        },
                    ]}
                    onPress={() => setSegment('transactions')}
                >
                    <Text
                        style={{
                            fontWeight: '700',
                            fontSize: FONT_SIZES.sm,
                            color: segment === 'transactions' ? colors.primary : textSub,
                        }}
                    >
                        Transactions
                    </Text>
                </TouchableOpacity>
            </View>

            <View style={styles.body}>
                {segment === 'payments' ? (
                    <PaymentsListScreen navigation={navigation} embedded />
                ) : (
                    <TransactionsListScreen navigation={navigation} embedded />
                )}
            </View>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: {
        paddingHorizontal: SPACING.xl,
        paddingTop: SPACING.sm,
        paddingBottom: SPACING.md,
    },
    title: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: '700',
    },
    sub: {
        fontSize: FONT_SIZES.xs,
        marginTop: SPACING.xs,
        lineHeight: 18,
    },
    segmentRow: {
        flexDirection: 'row',
        gap: SPACING.sm,
        paddingHorizontal: SPACING.xl,
        marginBottom: SPACING.sm,
    },
    segment: {
        flex: 1,
        paddingVertical: SPACING.md,
        borderRadius: BORDER_RADIUS.md,
        borderWidth: 1,
        alignItems: 'center',
    },
    body: { flex: 1 },
});
