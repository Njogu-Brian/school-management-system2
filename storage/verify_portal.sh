#!/bin/bash
for f in /tmp/p2.html /tmp/p1.html; do
  echo "=== $f ==="
  echo "report documents (articles): $(grep -o '<article' "$f" | wc -l)"
  echo "embedded report bodies:      $(grep -o 'Subject</th>' "$f" | wc -l)"
  echo "locked panels:               $(grep -o 'Report form unavailable' "$f" | wc -l)"
  echo "Next Term Fees blocks:       $(grep -o 'Next Term Fees' "$f" | wc -l)"
  echo "students:"
  grep -o 'class="student-name">[^<]*' "$f" | sed 's/class="student-name">/  - /'
  echo "fee totals shown:"
  grep -o 'class="fees-total">[^<]*' "$f" | sed 's/class="fees-total">/  /'
  echo "invoice links:"
  grep -o 'href="[^"]*invoice/[^"]*"' "$f" | sort -u | sed 's/^/  /'
  echo
done
