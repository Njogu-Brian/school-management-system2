#!/usr/bin/env python3
"""Equity text-block parser: keep the full narrative, never merge two money rows."""

import os
import sys
import unittest

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
sys.path.insert(0, os.path.join(ROOT, "app", "Services", "python"))

from equity_statement_parser import (  # noqa: E402
    _narrative_without_isolated_ref,
    parse_equity_transactions_from_text,
)


class EquityNarrativeTests(unittest.TestCase):
    def test_keeps_text_on_both_sides_of_the_s_ref(self):
        full = "BY:/623614658382/24-08- S40643062 2026 17:28"
        self.assertEqual(
            _narrative_without_isolated_ref(full, "S40643062"),
            "BY:/623614658382/24-08-2026 17:28",
        )

    def test_chicken_park_deposit_is_complete_and_not_merged_with_card_row(self):
        # Layout taken from the live Equity PDF (24/08/2026).
        text = """
Opening Balance
57,000.00
APP/STANLEY NGANGA A14BC988F50A A14BC988F50A
24/08/2026 24/08/2026 54594036 8,000.00 182,560.63
WAIRI/ 9 9
CHICKEN PARK-WAT-
627851XXXXXX
24/08/2026 24/08/2026 BY:/623614658382/24-08- S40643062 35,000.00 217,560.63 00038209
8996
2026 17:28
APP/MPESA/254727686069/A
EQA447A6DF5 A447A6DF58FE
24/08/2026 24/08/2026 447A6DF58FE7/ CAROLINE 54944590 8,100.00 209,460.63
"""
        rows = parse_equity_transactions_from_text(text)
        self.assertGreaterEqual(len(rows), 2)

        card = next(r for r in rows if str(r.get("transaction_code")) == "54594036")
        deposit = next(r for r in rows if str(r.get("transaction_code")) == "S40643062")
        mpesa = next(r for r in rows if str(r.get("transaction_code")) == "54944590")

        self.assertIn("WAIRI", card["particulars"].upper())
        self.assertIn("627851", card["particulars"])
        self.assertNotIn("BY:/623614658382", card["particulars"])
        self.assertNotIn("S40643062", card["particulars"])

        self.assertAlmostEqual(deposit["credit"], 35000.00)
        self.assertIn("CHICKEN PARK-WAT", deposit["particulars"].upper())
        self.assertIn("BY:/623614658382/24-08-2026 17:28", deposit["particulars"])
        self.assertTrue(
            deposit["particulars"].upper().startswith("CHICKEN PARK-WAT")
            or " CHICKEN PARK-WAT" in deposit["particulars"].upper()
        )
        self.assertNotIn("WAIRI", deposit["particulars"].upper())
        self.assertNotIn("627851", deposit["particulars"])
        self.assertNotIn("S40643062", deposit["particulars"])
        self.assertNotIn("APP/MPESA", deposit["particulars"].upper())
        self.assertNotIn("CAROLINE", deposit["particulars"].upper())

        self.assertIn("CAROLINE", mpesa["particulars"].upper())
        self.assertNotIn("BY:/623614658382", mpesa["particulars"])


if __name__ == "__main__":
    unittest.main()
