import type { ApplicationDetail } from '@erp/core';
import { ApplicationFieldSection } from '@erp/ui';
import React from 'react';
import { ScrollView } from 'react-native';

export interface StudentTabProps {
  application: ApplicationDetail;
}

export const StudentTab: React.FC<StudentTabProps> = ({ application }) => (
  <ScrollView showsVerticalScrollIndicator={false}>
    <ApplicationFieldSection
      title="Student details"
      rows={[
        { label: 'Full name', value: application.fullName },
        { label: 'Date of birth', value: application.dob },
        { label: 'Gender', value: application.gender },
        { label: 'NEMIS number', value: application.nemisNumber },
        { label: 'KNEC assessment number', value: application.knecAssessmentNumber },
        { label: 'Residential area', value: application.residentialArea },
        { label: 'Previous school', value: application.previousSchool },
        { label: 'Transfer reason', value: application.transferReason },
      ]}
    />
    <ApplicationFieldSection
      title="Medical & emergency"
      rows={[
        { label: 'Allergies', value: application.hasAllergies ? 'Yes' : 'No' },
        { label: 'Allergy notes', value: application.allergiesNotes },
        { label: 'Fully immunized', value: application.isFullyImmunized ? 'Yes' : 'No' },
        { label: 'Preferred hospital', value: application.preferredHospital },
        { label: 'Emergency contact', value: application.emergencyContactName },
        { label: 'Emergency phone', value: application.emergencyContactPhone },
      ]}
    />
  </ScrollView>
);
