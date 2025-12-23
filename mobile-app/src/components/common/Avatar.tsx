import React from 'react';
import {
    View,
    Text,
    TouchableOpacity,
    StyleSheet,
    Image,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface AvatarProps {
    name: string;
    imageUrl?: string;
    size?: number;
}

export const Avatar: React.FC<AvatarProps> = ({ name, imageUrl, size = 40 }) => {
    const { isDark, colors } = useTheme();

    const getInitials = (fullName: string) => {
        return fullName
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    if (imageUrl) {
        return (
            <Image
                source={{ uri: imageUrl }}
                style={[styles.avatar, { width: size, height: size, borderRadius: size / 2 }]}
            />
        );
    }

    return (
        <View
            style={[
                styles.avatar,
                {
                    width: size,
                    height: size,
                    borderRadius: size / 2,
                    backgroundColor: colors.primary + '20',
                },
            ]}
        >
            <Text style={[styles.initials, { color: colors.primary, fontSize: size * 0.4 }]}>
                {getInitials(name)}
            </Text>
        </View>
    );
};

const styles = StyleSheet.create({
    avatar: {
        alignItems: 'center',
        justifyContent: 'center',
    },
    initials: {
        fontWeight: 'bold',
    },
});
