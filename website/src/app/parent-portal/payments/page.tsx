import { Suspense } from "react";
import PaymentsClient from "./PaymentsClient";

export default function ParentPaymentsPage() {
  return (
    <Suspense fallback={<div className="p-16 text-center">Loading payments...</div>}>
      <PaymentsClient />
    </Suspense>
  );
}
