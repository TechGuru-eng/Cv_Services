import type { Metadata } from "next";
import { Inter, Poppins } from "next/font/google";
import "./globals.css";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
});

const poppins = Poppins({
  variable: "--font-poppins",
  weight: ["400", "500", "600", "700", "800"],
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "Le Guide Ultime des Cabinets RH | Précommande",
  description: "Réservez votre exemplaire aujourd'hui au tarif exceptionnel de lancement.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="fr" className={`${inter.variable} ${poppins.variable} scroll-smooth`}>
      <body className="font-sans antialiased min-h-screen flex flex-col selection:bg-gold selection:text-navy">
        {children}
      </body>
    </html>
  );
}
