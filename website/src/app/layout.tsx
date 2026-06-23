import type { Metadata } from "next";
import { Playfair_Display, DM_Sans } from "next/font/google";
import "./globals.css";
import { QueryProvider } from "@/components/providers/QueryProvider";
import { AnalyticsTracker } from "@/components/analytics/AnalyticsTracker";
import { BRAND } from "@/content/schoolContent";
import { assetPath } from "@/lib/assetPath";

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
    default: BRAND.shortName,
    template: `%s | ${BRAND.shortName}`,
  },
  description: `${BRAND.tagline} — Creche to Grade 9 Christian-centered education in Wangige, Kenya.`,
  icons: {
    icon: [{ url: assetPath("/logo.png"), type: "image/png" }],
    apple: [{ url: assetPath("/logo.png") }],
  },
  openGraph: {
    type: "website",
    locale: "en_KE",
    siteName: BRAND.shortName,
    images: [{ url: BRAND.logoUrl }],
  },
};

const schoolSchema = {
  "@context": "https://schema.org",
  "@type": "School",
  name: BRAND.name,
  alternateName: BRAND.shortName,
  description: BRAND.tagline,
  foundingDate: String(BRAND.founded),
  address: {
    "@type": "PostalAddress",
    addressLocality: "Wangige",
    addressRegion: "Kiambu",
    addressCountry: "KE",
  },
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
      <body className="min-h-full flex flex-col bg-rk-white font-sans text-rk-text antialiased">
        <QueryProvider>
          <AnalyticsTracker />
          {children}
        </QueryProvider>
      </body>
    </html>
  );
}
