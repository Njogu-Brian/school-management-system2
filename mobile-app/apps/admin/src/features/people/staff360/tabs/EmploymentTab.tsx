import type { StaffDetail } from '@erp/core';
import { StaffFieldSection } from '@erp/ui';
import React from 'react';
import { capitalizeStatus } from '../utils/formatters';

export interface EmploymentTabProps {
  staff: StaffDetail;
  canViewFinance: boolean;
}

export const EmploymentTab: React.FC<EmploymentTabProps> = ({ staff, canViewFinance }) => {
  const exemptions =
    staff.statutoryExemptions.length > 0 ? staff.statutoryExemptions.join(', ') : null;

  return (
    <>
      <StaffFieldSection
        title="Position & organisation"
        rows={[
          { label: 'Department', value: staff.departmentName },
          { label: 'Job title', value: staff.jobTitle },
          { label: 'Category', value: staff.staffCategory },
          { label: 'System role', value: staff.systemRole },
          { label: 'Supervisor', value: staff.supervisorName },
          { label: 'Max lessons / week', value: staff.maxLessonsPerWeek?.toString() ?? null },
        ]}
      />

      <StaffFieldSection
        title="Contract & tenure"
        rows={[
          { label: 'Employment status', value: capitalizeStatus(staff.employmentStatus ?? '') },
          { label: 'Employment type', value: staff.employmentType },
          { label: 'Hire date', value: staff.hireDate },
          { label: 'Termination date', value: staff.terminationDate },
          { label: 'Contract start', value: staff.contractStartDate },
          { label: 'Contract end', value: staff.contractEndDate },
        ]}
      />

      <StaffFieldSection
        title="Identity & contact"
        rows={[
          { label: 'ID number', value: staff.idNumber },
          { label: 'Date of birth', value: staff.dateOfBirth },
          { label: 'Gender', value: staff.gender },
          { label: 'Marital status', value: staff.maritalStatus },
          { label: 'Work email', value: staff.email },
          { label: 'Personal email', value: staff.personalEmail },
          { label: 'Phone', value: staff.phone },
          { label: 'Address', value: staff.residentialAddress },
        ]}
      />

      <StaffFieldSection
        title="Emergency contact"
        rows={[
          { label: 'Name', value: staff.emergencyContact.name },
          { label: 'Relationship', value: staff.emergencyContact.relationship },
          { label: 'Phone', value: staff.emergencyContact.phone },
        ]}
      />

      {canViewFinance ? (
        <StaffFieldSection
          title="Payroll & statutory"
          rows={[
            { label: 'Configured basic salary', value: staff.basicSalary?.toLocaleString('en-KE') },
            { label: 'Bank', value: staff.bankName },
            { label: 'Branch', value: staff.bankBranch },
            { label: 'Account', value: staff.bankAccount },
            { label: 'KRA PIN', value: staff.kraPin },
            { label: 'NSSF', value: staff.nssf },
            { label: 'NHIF', value: staff.nhif },
            { label: 'Statutory exemptions', value: exemptions },
          ]}
        />
      ) : null}
    </>
  );
};
