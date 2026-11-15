import type { Metadata } from "next";
import { Playfair_Display, DM_Sans } from "next/font/google";
import "./globals.css";
import { QueryProvider } from "@/components/providers/QueryProvider";
import { AnalyticsTracker } from "@/components/analytics/AnalyticsTracker";
import { LiveEditorRoot } from "@/components/live-editor/LiveEditorRoot";
import { BRAND } from "@/content/schoolContent";
import { assetPath } from "@/lib/assetPath";
import { PUBLIC_PAGES, SITE_NAME, SITE_URL, schoolJsonLd, websiteJsonLd } from "@/lib/seo";

const playfair = Playfair_Display({
  subsets: ["latin"],
  variable: "--font-serif",
});

const dmSans = DM_Sans({
  subsets: ["latin"],
  variable: "--font-sans",
});

const home = PUBLIC_PAGES[0];

export const metadata: Metadata = {
  metadataBase: new URL(SITE_URL),
  title: {
    default: home.title,
    template: `%s | ${SITE_NAME}`,
  },
  description: home.description,
  applicationName: SITE_NAME,
  keywords: [
    "Royal Kings Premier School",
    "Royal Kings School Wangige",
    "school in Wangige",
    "school in Kiambu",
    "Christian school Kenya",
    "CBC school Wangige",
    "creche Wangige",
    "junior secondary Wangige",
    "preschool in Wangige",
    "primary school in Wangige",
  ],
  alternates: { canonical: SITE_URL },
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      "max-image-preview": "large",
      "max-snippet": -1,
      "max-video-preview": -1,
    },
  },
  icons: {
    icon: [{ url: assetPath("/logo.png"), type: "image/png" }],
    apple: [{ url: assetPath("/logo.png") }],
  },
  openGraph: {
    type: "website",
    locale: "en_KE",
    url: SITE_URL,
    siteName: SITE_NAME,
    title: home.title,
    description: home.description,
    images: [{ url: BRAND.logoUrl, alt: SITE_NAME }],
  },
  twitter: {
    card: "summary_large_image",
    title: home.title,
    description: home.description,
    images: [BRAND.logoUrl],
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
          dangerouslySetInnerHTML={{ __html: JSON.stringify(schoolJsonLd()) }}
        />
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(websiteJsonLd()) }}
        />
      </head>
      <body
        className="min-h-full flex flex-col bg-rk-white font-sans text-rk-text antialiased"
        suppressHydrationWarning
      >
        <QueryProvider>
          <LiveEditorRoot>
            <AnalyticsTracker />
            {children}
          </LiveEditorRoot>
        </QueryProvider>
      </body>
    </html>
  );
}
