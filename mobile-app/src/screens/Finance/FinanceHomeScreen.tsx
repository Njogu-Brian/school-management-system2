import React from 'react';
import { View, Text, StyleSheet, SafeAreaView, TouchableOpacity, ScrollView } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { navigate: (name: string, params?: object) => void };
}

export const FinanceHomeScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;
    const surface = isDark ? colors.surfaceDark : BRAND.surface;
    const border = isDark ? colors.borderDark : BRAND.border;

    const items = [
        {
            title: 'Active invoices',
            subtitle: 'View issued and outstanding invoices',
            icon: 'receipt-long' as const,
            color: '#3b82f6',
            screen: 'InvoicesList' as const,
        },
        {
            title: 'Payments',
            subtitle: 'Recent receipts and allocations',
            icon: 'payments' as const,
            color: '#10b981',
            screen: 'PaymentsList' as const,
        },
    ];

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: bg }]}>
            <View style={styles.header}>
                <Text style={[styles.title, { color: textMain }]}>Finance</Text>
                <Text style={[styles.subtitle, { color: textSub }]}>
                    Create or batch invoices, credit/debit notes, and optional fees in the web portal. Record a payment from a
                    student&apos;s profile (Students tab).
                </Text>
            </View>
            <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
                {items.map((item) => (
                    <TouchableOpacity
                        key={item.screen}
                        style={[styles.card, { backgroundColor: surface, borderColor: border }]}
                        onPress={() => navigation.navigate(item.screen)}
                        activeOpacity={0.7}
                    >
                        <View style={[styles.iconWrap, { backgroundColor: item.color + '18' }]}>
                            <Icon name={item.icon} size={28} color={item.color} />
                        </View>
                        <View style={styles.cardText}>
                            <Text style={[styles.cardTitle, { color: textMain }]}>{item.title}</Text>
                            <Text style={[styles.cardSub, { color: textSub }]}>{item.subtitle}</Text>
                        </View>
                        <Icon name="chevron-right" size={24} color={textSub} />
                    </TouchableOpacity>
                ))}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: {
        paddingHorizontal: SPACING.xl,
        paddingTop: SPACING.md,
        paddingBottom: SPACING.lg,
    },
    title: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: '700',
    },
    subtitle: {
        fontSize: FONT_SIZES.sm,
        marginTop: SPACING.sm,
        lineHeight: 20,
    },
    scroll: {
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.xl,
        gap: SPACING.md,
    },
    card: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: SPACING.lg,
        borderRadius: RADIUS.card,
        borderWidth: 1,
        gap: SPACING.md,
    },
    iconWrap: {
        width: 52,
        height: 52,
        borderRadius: RADIUS.md,
        alignItems: 'center',
        justifyContent: 'center',
    },
    cardText: { flex: 1 },
    cardTitle: {
        fontSize: FONT_SIZES.md,
        fontWeight: '600',
    },
    cardSub: {
        fontSize: FONT_SIZES.xs,
        marginTop: 4,
    },
});
