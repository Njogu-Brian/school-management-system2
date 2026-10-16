import type { Metadata } from "next";
import { Suspense } from "react";
import { PRIVATE_PAGE_METADATA } from "@/lib/seo";
import TrackApplicationClient from "./TrackClient";

export const metadata: Metadata = {
  ...PRIVATE_PAGE_METADATA,
  title: "Track Application",
};

export default function TrackApplicationPage() {
  return (
    <Suspense fallback={<div className="py-20 text-center">Loading...</div>}>
      <TrackApplicationClient />
    </Suspense>
  );
}
