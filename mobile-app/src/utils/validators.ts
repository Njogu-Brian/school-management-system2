export const validators = {
    // Email validation
    email: (value: string): string | undefined => {
        if (!value) {
            return 'Email is required';
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            return 'Please enter a valid email';
        }
        return undefined;
    },

    // Password validation
    password: (value: string): string | undefined => {
        if (!value) {
            return 'Password is required';
        }
        if (value.length < 8) {
            return 'Password must be at least 8 characters';
        }
        return undefined;
    },

    // Phone validation
    phone: (value: string): string | undefined => {
        if (!value) {
            return 'Phone number is required';
        }
        const phoneRegex = /^\+?[1-9]\d{1,14}$/;
        if (!phoneRegex.test(value.replace(/\s/g, ''))) {
            return 'Please enter a valid phone number';
        }
        return undefined;
    },

    // Required field
    required: (value: any): string | undefined => {
        if (!value || (typeof value === 'string' && !value.trim())) {
            return 'This field is required';
        }
        return undefined;
    },

    // Confirm password
    confirmPassword: (value: string, password: string): string | undefined => {
        if (!value) {
            return 'Please confirm your password';
        }
        if (value !== password) {
            return 'Passwords do not match';
        }
        return undefined;
    },

    // OTP code
    otpCode: (value: string): string | undefined => {
        if (!value) {
            return 'OTP code is required';
        }
        if (!/^\d{6}$/.test(value)) {
            return 'Please enter a valid 6-digit code';
        }
        return undefined;
    },
};
