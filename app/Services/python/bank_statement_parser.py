#!/usr/bin/env python3
"""
Bank Statement Parser for MPESA and Equity Bank
Extracts transactions from PDF bank statements
"""

import sys
import json
import re
import pdfplumber
from datetime import datetime
from typing import List, Dict, Optional

class BankStatementParser:
    def __init__(self):
        self.transactions = []
        
    def parse_mpesa_statement(self, pdf_path: str) -> List[Dict]:
        """Parse MPESA statement PDF"""
        transactions = []
        
        try:
            with pdfplumber.open(pdf_path) as pdf:
                for page in pdf.pages:
                    text = page.extract_text(layout=True, x_tolerance=1, y_tolerance=1) or ""
                    tables = page.extract_tables()
                    
                    # Try to extract from tables first
                    if tables:
                        for table in tables:
                            if len(table) > 1:  # Has header row
                                for row in table[1:]:  # Skip header
                                    if len(row) >= 4:
                                        txn = self._parse_mpesa_row(row)
                                        if txn:
                                            transactions.append(txn)
                    
                    # Fallback to text extraction
                    if not transactions:
                        transactions.extend(self._parse_mpesa_text(text))
                        
        except Exception as e:
            sys.stderr.write(f"Error parsing MPESA statement: {str(e)}\n")
            
        return transactions
    
    def parse_equity_statement(self, pdf_path: str) -> List[Dict]:
        """Parse Equity Bank statement PDF"""
        transactions = []
        
        try:
            with pdfplumber.open(pdf_path) as pdf:
                for page in pdf.pages:
                    text = page.extract_text(layout=True, x_tolerance=1, y_tolerance=1) or ""
                    tables = page.extract_tables()
                    
                    # Try to extract from tables first
                    if tables:
                        for table in tables:
                            if len(table) > 1:
                                for row in table[1:]:
                                    if len(row) >= 4:
                                        txn = self._parse_equity_row(row)
                                        if txn:
                                            transactions.append(txn)
                    
                    # Fallback to text extraction
                    if not transactions:
                        transactions.extend(self._parse_equity_text(text))
                        
        except Exception as e:
            sys.stderr.write(f"Error parsing Equity statement: {str(e)}\n")
            
        return transactions
    
    def _parse_mpesa_row(self, row: List) -> Optional[Dict]:
        """Parse a single MPESA table row"""
        try:
            # MPESA format: Date | Time | Details | Phone | Amount | Balance
            if len(row) < 5:
                return None
                
            date_str = str(row[0]).strip()
            details = str(row[2]).strip() if len(row) > 2 else ""
            phone = str(row[3]).strip() if len(row) > 3 else ""
            amount_str = str(row[4]).strip() if len(row) > 4 else ""
            
            # Parse date
            transaction_date = self._parse_date(date_str)
            if not transaction_date:
                return None
            
            # Parse amount
            amount = self._parse_amount(amount_str)
            if amount is None:
                return None
            
            # Determine transaction type
            transaction_type = 'credit' if amount > 0 else 'debit'
            
            # Extract reference number from details
            reference = self._extract_reference(details)
            
            # Extract phone number
            phone = self._extract_phone(phone or details)
            
            return {
                'transaction_date': transaction_date,
                'amount': abs(amount),
                'transaction_type': transaction_type,
                'reference_number': reference,
                'description': details,
                'phone_number': phone,
                'bank_type': 'mpesa'
            }
        except Exception:
            return None
    
    def _parse_equity_row(self, row: List) -> Optional[Dict]:
        """Parse a single Equity Bank table row"""
        try:
            # Equity format: Date | Description | Debit | Credit | Balance
            if len(row) < 3:
                return None
                
            date_str = str(row[0]).strip()
            description = str(row[1]).strip() if len(row) > 1 else ""
            debit_str = str(row[2]).strip() if len(row) > 2 else ""
            credit_str = str(row[3]).strip() if len(row) > 3 else ""
            
            # Parse date
            transaction_date = self._parse_date(date_str)
            if not transaction_date:
                return None
            
            # Determine amount and type
            debit = self._parse_amount(debit_str) or 0
            credit = self._parse_amount(credit_str) or 0
            
            if debit > 0:
                amount = debit
                transaction_type = 'debit'
            elif credit > 0:
                amount = credit
                transaction_type = 'credit'
            else:
                return None
            
            # Extract reference number
            reference = self._extract_reference(description)
            
            # Extract phone number
            phone = self._extract_phone(description)
            
            return {
                'transaction_date': transaction_date,
                'amount': amount,
                'transaction_type': transaction_type,
                'reference_number': reference,
                'description': description,
                'phone_number': phone,
                'bank_type': 'equity'
            }
        except Exception:
            return None
    
    def _parse_mpesa_text(self, text: str) -> List[Dict]:
        """Parse MPESA statement from text"""
        transactions = []
        lines = text.split('\n')
        
        for line in lines:
            # Look for transaction patterns
            # Format: DD/MM/YYYY HH:MM Description Phone Amount
            pattern = r'(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})\s+(\d{1,2}:\d{2})?\s+(.+?)\s+(\d{10,12})?\s+([\d,]+\.?\d*)'
            match = re.search(pattern, line)
            
            if match:
                date_str = match.group(1)
                description = match.group(3)
                phone = match.group(4) or ""
                amount_str = match.group(5)
                
                transaction_date = self._parse_date(date_str)
                amount = self._parse_amount(amount_str)
                
                if transaction_date and amount:
                    transactions.append({
                        'transaction_date': transaction_date,
                        'amount': abs(amount),
                        'transaction_type': 'credit' if amount > 0 else 'debit',
                        'reference_number': self._extract_reference(description),
                        'description': description,
                        'phone_number': self._extract_phone(phone or description),
                        'bank_type': 'mpesa'
                    })
        
        return transactions
    
    def _parse_equity_text(self, text: str) -> List[Dict]:
        """Parse Equity Bank statement from text"""
        transactions = []
        lines = text.split('\n')
        
        for line in lines:
            # Look for transaction patterns
            # Format: DD/MM/YYYY Description Debit Credit
            pattern = r'(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})\s+(.+?)\s+([\d,]+\.?\d*)?\s+([\d,]+\.?\d*)?'
            match = re.search(pattern, line)
            
            if match:
                date_str = match.group(1)
                description = match.group(2)
                debit_str = match.group(3) or ""
                credit_str = match.group(4) or ""
                
                transaction_date = self._parse_date(date_str)
                debit = self._parse_amount(debit_str) or 0
                credit = self._parse_amount(credit_str) or 0
                
                if transaction_date:
                    if debit > 0:
                        transactions.append({
                            'transaction_date': transaction_date,
                            'amount': debit,
                            'transaction_type': 'debit',
                            'reference_number': self._extract_reference(description),
                            'description': description,
                            'phone_number': self._extract_phone(description),
                            'bank_type': 'equity'
                        })
                    elif credit > 0:
                        transactions.append({
                            'transaction_date': transaction_date,
                            'amount': credit,
                            'transaction_type': 'credit',
                            'reference_number': self._extract_reference(description),
                            'description': description,
                            'phone_number': self._extract_phone(description),
                            'bank_type': 'equity'
                        })
        
        return transactions
    
    def _parse_date(self, date_str: str) -> Optional[str]:
        """Parse date string to YYYY-MM-DD format"""
        if not date_str:
            return None
            
        # Remove time if present
        date_str = date_str.split()[0] if ' ' in date_str else date_str
        
        # Try different date formats
        formats = [
            '%d/%m/%Y',
            '%d-%m-%Y',
            '%d/%m/%y',
            '%d-%m-%y',
            '%Y-%m-%d',
            '%Y/%m/%d',
        ]
        
        for fmt in formats:
            try:
                dt = datetime.strptime(date_str, fmt)
                return dt.strftime('%Y-%m-%d')
            except ValueError:
                continue
        
        return None
    
    def _parse_amount(self, amount_str: str) -> Optional[float]:
        """Parse amount string to float"""
        if not amount_str:
            return None
            
        # Remove commas and currency symbols
        amount_str = re.sub(r'[,\sKShKES]', '', str(amount_str))
        
        try:
            return float(amount_str)
        except ValueError:
            return None
    
    def _extract_phone(self, text: str) -> Optional[str]:
        """Extract phone number from text"""
        if not text:
            return None
            
        # Kenyan phone patterns: 254XXXXXXXXX, 07XXXXXXXX, +254XXXXXXXXX
        patterns = [
            r'254\d{9}',
            r'\+254\d{9}',
            r'07\d{8}',
            r'01\d{8}',
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text)
            if match:
                phone = match.group(0)
                # Normalize to 254XXXXXXXXX format
                if phone.startswith('0'):
                    phone = '254' + phone[1:]
                elif phone.startswith('+254'):
                    phone = phone[1:]
                return phone
        
        return None
    
    def _extract_reference(self, text: str) -> Optional[str]:
        """Extract reference number from description"""
        if not text:
            return None
            
        # Look for common reference patterns
        patterns = [
            r'[A-Z]{2,}\d{6,}',  # ABC123456
            r'\d{8,}',  # Long numeric references
            r'[A-Z0-9]{10,}',  # Alphanumeric references
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text)
            if match:
                return match.group(0)
        
        return None


def main():
    if len(sys.argv) < 3:
        print(json.dumps([]))
        sys.exit(1)
    
    pdf_path = sys.argv[1]
    bank_type = sys.argv[2].lower()  # 'mpesa' or 'equity'
    
    parser = BankStatementParser()
    
    if bank_type == 'mpesa':
        transactions = parser.parse_mpesa_statement(pdf_path)
    elif bank_type == 'equity':
        transactions = parser.parse_equity_statement(pdf_path)
    else:
        # Auto-detect
        transactions = parser.parse_mpesa_statement(pdf_path)
        if not transactions:
            transactions = parser.parse_equity_statement(pdf_path)
    
    print(json.dumps(transactions, indent=2))


if __name__ == '__main__':
    main()

