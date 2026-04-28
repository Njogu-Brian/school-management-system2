import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';

type FeeStatus = 'cleared' | 'pending';

export const FeeStatusBadge: React.FC<{
    fee_status?: FeeStatus | null;
    outstanding_balance?: number | null;
    compact?: boolean;
}> = ({ fee_status, outstanding_balance: _outstanding_balance, compact }) => {
    if (!fee_status) return null;
    const cleared = fee_status === 'cleared';

    const bg = cleared ? '#E8F5E9' : '#FFEBEE';
    const border = cleared ? '#2E7D32' : '#C62828';
    const fg = cleared ? '#2E7D32' : '#C62828';

    // Important: Teachers/drivers must not see any fee amounts here. The server decides
    // pending vs cleared (thresholds, plans, deadlines). UI only displays the status.
    const label = cleared ? 'Cleared' : 'Pending';

    return (
        <View
            style={[
                styles.badge,
                compact && styles.badgeCompact,
                { backgroundColor: bg, borderColor: border },
            ]}
        >
            <Icon name={cleared ? 'check-circle' : 'error'} size={compact ? 11 : 12} color={fg} />
            <Text style={[styles.text, compact && styles.textCompact, { color: fg }]} numberOfLines={1}>
                {label}
            </Text>
        </View>
    );
};

const styles = StyleSheet.create({
    badge: {
        alignSelf: 'flex-start',
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 6,
        paddingVertical: 2,
        borderRadius: 8,
        borderWidth: 1,
        marginTop: 4,
        maxWidth: '95%',
        gap: 2,
    },
    badgeCompact: {
        marginTop: 0,
        paddingVertical: 1,
    },
    text: { fontSize: 10, fontWeight: '600' },
    textCompact: { fontSize: 9 },
});

