import Link from "next/link";
import { ArrowLeft } from "lucide-react";

export const metadata = {
  title: "Politique de Confidentialité | Le Guide Ultime des Cabinets RH",
  description: "Politique de confidentialité et protection de vos données personnelles.",
};

export default function PolitiqueConfidentialite() {
  return (
    <main className="min-h-screen bg-background py-16 px-6 relative">
      <div className="max-w-4xl mx-auto">
        <Link href="/" className="inline-flex items-center gap-2 text-navy hover:text-gold transition-colors font-medium mb-10">
          <ArrowLeft className="w-5 h-5" /> Retour à l'accueil
        </Link>
        
        <div className="bg-white rounded-3xl p-8 md:p-12 shadow-sm border border-slate-200">
          <h1 className="text-3xl md:text-5xl font-poppins font-bold text-navy mb-8">Politique de Confidentialité</h1>
          
          <div className="space-y-8 text-slate-600 leading-relaxed">
            <section>
              <h2 className="text-xl font-bold text-navy mb-4">1. Collecte des données personnelles</h2>
              <p>
                Dans le cadre de votre précommande, nous collectons les informations nécessaires au traitement de votre demande : votre nom complet, votre adresse e-mail, votre numéro WhatsApp, votre ville et votre profession. Ces données nous permettent de vous identifier et de vous envoyer votre guide ainsi que les bonus exclusifs lors du lancement officiel.
              </p>
            </section>
            
            <section>
              <h2 className="text-xl font-bold text-navy mb-4">2. Utilisation des données</h2>
              <p>
                Vos informations personnelles sont utilisées uniquement pour les finalités suivantes :
              </p>
              <ul className="list-disc pl-5 mt-2 space-y-2">
                <li>Traiter votre précommande.</li>
                <li>Vous livrer le produit numérique commandé.</li>
                <li>Vous communiquer des mises à jour importantes concernant le lancement (si vous avez accepté de les recevoir).</li>
                <li>Améliorer nos services de mise en relation professionnelle.</li>
              </ul>
            </section>
            
            <section>
              <h2 className="text-xl font-bold text-navy mb-4">3. Protection et Sécurité</h2>
              <p>
                Nous prenons la sécurité de vos données très au sérieux. Les informations que vous nous confiez sont stockées sur des serveurs sécurisés et ne sont en aucun cas vendues, échangées ou transférées à des tiers sans votre consentement explicite, sauf si la loi l'exige.
              </p>
            </section>
            
            <section>
              <h2 className="text-xl font-bold text-navy mb-4">4. Vos droits</h2>
              <p>
                Vous disposez d'un droit d'accès, de rectification et de suppression de vos données personnelles. Vous pouvez à tout moment nous contacter pour demander l'application de ces droits ou pour vous désinscrire de nos communications.
              </p>
            </section>
            
            <section>
              <h2 className="text-xl font-bold text-navy mb-4">5. Contact</h2>
              <p>
                Pour toute question concernant notre politique de confidentialité ou le traitement de vos données, veuillez nous contacter à l'adresse fournie lors du processus de réservation.
              </p>
            </section>
          </div>
          
          <div className="mt-12 pt-8 border-t border-slate-100 text-sm text-slate-500">
            Dernière mise à jour : {new Date().toLocaleDateString('fr-FR')}
          </div>
        </div>
      </div>
    </main>
  );
}
