export const formatters = {
    formatDate: (date: string | Date): string => {
        if (!date) return 'N/A';
        const d = new Date(date);
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    },

    formatDateTime: (date: string | Date): string => {
        if (!date) return 'N/A';
        const d = new Date(date);
        return d.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    },

    formatCurrency: (amount: number): string => {
        if (amount === null || amount === undefined) return 'KES 0.00';
        return `KES ${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    },

    formatPercentage: (value: number): string => {
        if (value === null || value === undefined) return '0%';
        return `${value.toFixed(1)}%`;
    },

    getInitials: (name: string): string => {
        if (!name) return '??';
        const names = name.trim().split(' ');
        if (names.length === 1) {
            return names[0].substring(0, 2).toUpperCase();
        }
        return (names[0][0] + names[names.length - 1][0]).toUpperCase();
    },

    truncate: (str: string, length: number): string => {
        if (!str) return '';
        if (str.length <= length) return str;
        return str.substring(0, length) + '...';
    },

    capitalize: (str: string): string => {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    },

    capitalizeWords: (str: string): string => {
        if (!str) return '';
        return str
            .split(' ')
            .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    },

    getRelativeTime: (date: string | Date): string => {
        if (!date) return '';
        const d = new Date(date);
        const now = new Date();
        const diffMs = now.getTime() - d.getTime();
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;

        return formatters.formatDate(date);
    },

    formatPhoneNumber: (phone: string): string => {
        if (!phone) return '';
        // Remove all non-digit characters
        const cleaned = phone.replace(/\D/g, '');

        // Format as: +254 712 345 678
        if (cleaned.length === 12 && cleaned.startsWith('254')) {
            return `+${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6, 9)} ${cleaned.slice(9)}`;
        }

        // Format as: 0712 345 678
        if (cleaned.length === 10 && cleaned.startsWith('0')) {
            return `${cleaned.slice(0, 4)} ${cleaned.slice(4, 7)} ${cleaned.slice(7)}`;
        }

        return phone;
    },

    formatFileSize: (bytes: number): string => {
        if (!bytes) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    },

    formatGrade: (percentage: number): string => {
        if (percentage >= 80) return 'A';
        if (percentage >= 70) return 'B';
        if (percentage >= 60) return 'C';
        if (percentage >= 50) return 'D';
        return 'E';
    },

    getGradeColor: (grade: string): string => {
        switch (grade.toUpperCase()) {
            case 'A':
                return '#10b981'; // green
            case 'B':
                return '#3b82f6'; // blue
            case 'C':
                return '#f59e0b'; // yellow
            case 'D':
                return '#f97316'; // orange
            case 'E':
                return '#ef4444'; // red
            default:
                return '#6b7280'; // gray
        }
    },

    formatDuration: (minutes: number): string => {
        if (minutes < 60) return `${minutes}min`;
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return mins > 0 ? `${hours}h ${mins}min` : `${hours}h`;
    },
};
