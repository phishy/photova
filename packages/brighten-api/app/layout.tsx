import type { Metadata } from 'next';
import './globals.css';

const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || 'https://brighten.bugnjeff.com';
const ogImageUrl = `${siteUrl}/api/og`;

export const metadata: Metadata = {
  title: 'Brighten API',
  description: 'AI image processing in one API call. Background removal, upscaling, restoration, colorization, and more.',
  metadataBase: new URL(siteUrl),
  openGraph: {
    title: 'Brighten API',
    description: 'AI image processing in one API call. Background removal, upscaling, restoration, and more.',
    url: siteUrl,
    siteName: 'Brighten',
    images: [
      {
        url: ogImageUrl,
        width: 1200,
        height: 630,
        alt: 'Brighten API - AI image processing in one API call',
      },
    ],
    locale: 'en_US',
    type: 'website',
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Brighten API',
    description: 'AI image processing in one API call. Background removal, upscaling, restoration, and more.',
    images: [ogImageUrl],
  },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
