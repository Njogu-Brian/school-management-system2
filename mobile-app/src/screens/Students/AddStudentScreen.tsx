import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TextInput,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Card } from '@components/common/Card';
import { Input } from '@components/common/Input';
import { studentsApi } from '@api/students.api';
import { SPACING, FONT_SIZES } from '@constants/theme';

interface AddStudentScreenProps {
    navigation: any;
}

export const AddStudentScreen: React.FC<AddStudentScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [loading, setLoading] = useState(false);

    const [formData, setFormData] = useState({
        first_name: '',
        middle_name: '',
        last_name: '',
        admission_number: '',
        date_of_birth: '',
        gender: 'male',
        class_id: '',
        stream_id: '',
        email: '',
        phone: '',
        address: '',
        blood_group: '',
        guardian_name: '',
        guardian_phone: '',
        guardian_email: '',
        guardian_relationship: '',
    });

    const handleInputChange = (field: string, value: string) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
    };

    const validateForm = (): boolean => {
        if (!formData.first_name || !formData.last_name) {
            Alert.alert('Validation Error', 'First name and last name are required');
            return false;
        }

        if (!formData.admission_number) {
            Alert.alert('Validation Error', 'Admission number is required');
            return false;
        }

        if (!formData.date_of_birth) {
            Alert.alert('Validation Error', 'Date of birth is required');
            return false;
        }

        if (!formData.class_id) {
            Alert.alert('Validation Error', 'Please select a class');
            return false;
        }

        return true;
    };

    const handleSubmit = async () => {
        if (!validateForm()) return;

        setLoading(true);
        try {
            const response = await studentsApi.createStudent(formData);

            if (response.success) {
                Alert.alert('Success', 'Student added successfully', [
                    { text: 'OK', onPress: () => navigation.goBack() },
                ]);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to add student');
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <ScrollView style={styles.content}>
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Personal Information
                    </Text>

                    <Input
                        label="First Name *"
                        value={formData.first_name}
                        onChangeText={(value) => handleInputChange('first_name', value)}
                        placeholder="Enter first name"
                    />

                    <Input
                        label="Middle Name"
                        value={formData.middle_name}
                        onChangeText={(value) => handleInputChange('middle_name', value)}
                        placeholder="Enter middle name"
                    />

                    <Input
                        label="Last Name *"
                        value={formData.last_name}
                        onChangeText={(value) => handleInputChange('last_name', value)}
                        placeholder="Enter last name"
                    />

                    <Input
                        label="Admission Number *"
                        value={formData.admission_number}
                        onChangeText={(value) => handleInputChange('admission_number', value)}
                        placeholder="Enter admission number"
                    />

                    <Input
                        label="Date of Birth *"
                        value={formData.date_of_birth}
                        onChangeText={(value) => handleInputChange('date_of_birth', value)}
                        placeholder="YYYY-MM-DD"
                    />

                    <Input
                        label="Gender"
                        value={formData.gender}
                        onChangeText={(value) => handleInputChange('gender', value)}
                        placeholder="Male/Female"
                    />

                    <Input
                        label="Blood Group"
                        value={formData.blood_group}
                        onChangeText={(value) => handleInputChange('blood_group', value)}
                        placeholder="e.g., A+"
                    />
                </Card>

                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Contact Information
                    </Text>

                    <Input
                        label="Email"
                        value={formData.email}
                        onChangeText={(value) => handleInputChange('email', value)}
                        placeholder="student@example.com"
                        keyboardType="email-address"
                    />

                    <Input
                        label="Phone"
                        value={formData.phone}
                        onChangeText={(value) => handleInputChange('phone', value)}
                        placeholder="Phone number"
                        keyboardType="phone-pad"
                    />

                    <Input
                        label="Address"
                        value={formData.address}
                        onChangeText={(value) => handleInputChange('address', value)}
                        placeholder="Residential address"
                        multiline
                        numberOfLines={3}
                    />
                </Card>

                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Guardian Information
                    </Text>

                    <Input
                        label="Guardian Name"
                        value={formData.guardian_name}
                        onChangeText={(value) => handleInputChange('guardian_name', value)}
                        placeholder="Enter guardian name"
                    />

                    <Input
                        label="Guardian Phone"
                        value={formData.guardian_phone}
                        onChangeText={(value) => handleInputChange('guardian_phone', value)}
                        placeholder="Guardian phone number"
                        keyboardType="phone-pad"
                    />

                    <Input
                        label="Guardian Email"
                        value={formData.guardian_email}
                        onChangeText={(value) => handleInputChange('guardian_email', value)}
                        placeholder="guardian@example.com"
                        keyboardType="email-address"
                    />

                    <Input
                        label="Relationship"
                        value={formData.guardian_relationship}
                        onChangeText={(value) => handleInputChange('guardian_relationship', value)}
                        placeholder="e.g., Father, Mother, Guardian"
                    />
                </Card>

                <View style={styles.buttonContainer}>
                    <Button
                        title="Cancel"
                        onPress={() => navigation.goBack()}
                        variant="outline"
                        fullWidth
                        style={styles.button}
                    />
                    <Button
                        title="Add Student"
                        onPress={handleSubmit}
                        loading={loading}
                        fullWidth
                        style={styles.button}
                    />
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    content: {
        flex: 1,
        padding: SPACING.xl,
    },
    section: {
        marginBottom: SPACING.lg,
    },
    sectionTitle: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
        marginBottom: SPACING.md,
    },
    buttonContainer: {
        flexDirection: 'row',
        gap: SPACING.md,
        marginTop: SPACING.lg,
        marginBottom: SPACING.xxl,
    },
    button: {
        flex: 1,
    },
});
