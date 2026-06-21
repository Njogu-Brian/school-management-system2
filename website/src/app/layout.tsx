import type { Metadata } from "next";
import { Playfair_Display, DM_Sans } from "next/font/google";
import "./globals.css";
import { QueryProvider } from "@/components/providers/QueryProvider";
import { AnalyticsTracker } from "@/components/analytics/AnalyticsTracker";

const playfair = Playfair_Display({
  subsets: ["latin"],
  variable: "--font-serif",
});

const dmSans = DM_Sans({
  subsets: ["latin"],
  variable: "--font-sans",
});

export const metadata: Metadata = {
  title: {
    default: "Royal Kings Education Centre",
    template: "%s | Royal Kings Education Centre",
  },
  description: "Where Little Steps Grow Into Great Futures — Creche to Grade 9 Christian-centered education.",
  openGraph: {
    type: "website",
    locale: "en_KE",
    siteName: "Royal Kings Education Centre",
  },
};

const schoolSchema = {
  "@context": "https://schema.org",
  "@type": "School",
  name: "Royal Kings Education Centre",
  description: "Where Little Steps Grow Into Great Futures",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className={`${playfair.variable} ${dmSans.variable} h-full`}>
      <head>
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(schoolSchema) }}
        />
      </head>
      <body className="min-h-full flex flex-col bg-white font-sans text-[#2a1145] antialiased">
        <QueryProvider>
          <AnalyticsTracker />
          {children}
        </QueryProvider>
      </body>
    </html>
  );
}
