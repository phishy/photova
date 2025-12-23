import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Brighten - Next.js Example',
  description: 'Photo editor SDK example with Next.js',
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
