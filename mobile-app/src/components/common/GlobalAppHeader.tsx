import React from 'react';
import { SafeAreaView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { SPACING, FONT_SIZES } from '@constants/theme';

interface GlobalAppHeaderProps {
    title: string;
    onBack: () => void;
    onSettings?: () => void;
    showSettings?: boolean;
}

export const GlobalAppHeader: React.FC<GlobalAppHeaderProps> = ({
    title,
    onBack,
    onSettings,
    showSettings = true,
}) => {
    return (
        <SafeAreaView style={styles.safeArea}>
            <View style={styles.row}>
                <TouchableOpacity onPress={onBack} style={styles.iconButton} hitSlop={10}>
                    <Icon name="arrow-back" size={24} color="#fff" />
                </TouchableOpacity>
                <Text style={styles.title} numberOfLines={1}>
                    {title}
                </Text>
                {showSettings && onSettings ? (
                    <TouchableOpacity onPress={onSettings} style={styles.iconButton} hitSlop={10}>
                        <Icon name="settings" size={22} color="#fff" />
                    </TouchableOpacity>
                ) : (
                    <View style={styles.iconSpacer} />
                )}
            </View>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    safeArea: {
        backgroundColor: '#5F2EEA',
    },
    row: {
        height: 56,
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: SPACING.lg,
    },
    iconButton: {
        width: 36,
        height: 36,
        alignItems: 'center',
        justifyContent: 'center',
    },
    iconSpacer: {
        width: 36,
        height: 36,
    },
    title: {
        flex: 1,
        color: '#fff',
        fontSize: FONT_SIZES.lg,
        fontWeight: '700',
        marginHorizontal: SPACING.sm,
    },
});
