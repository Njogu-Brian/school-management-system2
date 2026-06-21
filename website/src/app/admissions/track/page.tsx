import { Suspense } from "react";
import TrackApplicationClient from "./TrackClient";

export default function TrackApplicationPage() {
  return (
    <Suspense fallback={<div className="py-20 text-center">Loading...</div>}>
      <TrackApplicationClient />
    </Suspense>
  );
}
