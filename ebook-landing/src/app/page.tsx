"use client";

import { motion } from "framer-motion";
import { ArrowRight, CheckCircle2, ChevronDown, Download, Lock, Star, Search, MapPin, Briefcase, Users, Globe, LineChart, BookOpen, UserCircle, MessageSquare } from "lucide-react";
import Image from "next/image";
import { useEffect, useState } from "react";

const FadeIn = ({ children, delay = 0, className = "" }: { children: React.ReactNode, delay?: number, className?: string }) => (
  <motion.div
    initial={{ opacity: 0, y: 20 }}
    whileInView={{ opacity: 1, y: 0 }}
    viewport={{ once: true, margin: "-50px" }}
    transition={{ duration: 0.6, delay }}
    className={className}
  >
    {children}
  </motion.div>
);

export default function LandingPage() {
  const [timeLeft, setTimeLeft] = useState({ days: 0, hours: 0, minutes: 0, seconds: 0 });
  const [openFaq, setOpenFaq] = useState<number | null>(0);

  const [formData, setFormData] = useState({ fullName: "", email: "", whatsapp: "", city: "", profession: "" });
  const [sessionId, setSessionId] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  useEffect(() => {
    let sid = localStorage.getItem("ebook_session_id");
    if (!sid) {
      sid = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
      localStorage.setItem("ebook_session_id", sid);
    }
    setSessionId(sid);

    fetch("/api/track", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ sessionId: sid, referrer: document.referrer }),
    }).catch(console.error);
  }, []);

  const handlePartialSave = () => {
    if (!sessionId) return;
    if (formData.email || formData.whatsapp) {
      fetch("/api/track", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ sessionId, email: formData.email, whatsapp: formData.whatsapp }),
      }).catch(console.error);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      const res = await fetch("/api/subscribe", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...formData, sessionId }),
      });
      if (res.ok) setIsSuccess(true);
      else alert("Une erreur s'est produite. Veuillez réessayer.");
    } catch (error) {
      console.error(error);
    } finally {
      setIsSubmitting(false);
    }
  };

  useEffect(() => {
    const targetDate = new Date("2026-08-25T00:00:00+01:00").getTime();
    const interval = setInterval(() => {
      const now = new Date().getTime();
      const difference = targetDate - now;
      if (difference > 0) {
        setTimeLeft({
          days: Math.floor(difference / (1000 * 60 * 60 * 24)),
          hours: Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
          minutes: Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60)),
          seconds: Math.floor((difference % (1000 * 60)) / 1000)
        });
      } else clearInterval(interval);
    }, 1000);
    return () => clearInterval(interval);
  }, []);

  const whatsInside = [
    { icon: <Search />, title: "Analyse détaillée de plus de 30 cabinets RH" },
    { icon: <MapPin />, title: "Coordonnées complètes" },
    { icon: <Briefcase />, title: "Services proposés" },
    { icon: <Users />, title: "Pages LinkedIn" },
    { icon: <Globe />, title: "Sites web" },
    { icon: <LineChart />, title: "Analyses et conseils" },
  ];

  const bonuses = [
    { emoji: "📘", title: "Le Répertoire des Entreprises qui Recrutent Régulièrement au Cameroun", value: "10 000 XAF", details: ["Noms des entreprises", "Secteurs d'activité", "Adresses et contacts", "Fréquence de recrutement"] },
    { emoji: "📗", title: "Le Répertoire Premium des Contacts Officiels RH", value: "15 000 XAF", details: ["Emails officiels RH", "Pages carrières", "Pages LinkedIn", "Procédures de recrutement", "Canaux de contact officiels"] },
    { emoji: "📙", title: "Comment développer son réseau avec les recruteurs sur LinkedIn", value: "20 000 XAF", details: ["Comment optimiser votre profil", "Comment identifier les recruteurs", "Comment envoyer des invitations professionnelles", "Comment engager des conversations", "Comment rester visible", "Comment bâtir des relations durables"] },
    { emoji: "📕", title: "50 modèles de messages professionnels", value: "5 000 XAF", details: ["LinkedIn", "Email", "Relance", "Networking", "Recommandations", "Suivi d'entretien"] },
  ];

  const faqs = [
    { q: "À qui s'adresse ce guide ?", a: "Ce guide est conçu pour les chercheurs d'emploi, les professionnels en transition de carrière, et les dirigeants d'entreprise qui souhaitent comprendre le marché du recrutement au Cameroun." },
    { q: "Comment vais-je le recevoir ?", a: "Dès votre achat et le lancement officiel le 25 Août 2026, vous recevrez un email contenant un lien sécurisé pour télécharger l'ensemble du guide et de ses bonus." },
    { q: "Est-ce un format numérique ?", a: "Oui, le guide et tous les bonus sont au format numérique (PDF) pour un accès immédiat sur tous vos appareils (ordinateur, tablette, smartphone)." },
    { q: "Les entreprises peuvent-elles l'utiliser ?", a: "Absolument. Les entreprises cherchant des partenaires RH ou souhaitant comprendre le paysage des agences de recrutement y trouveront des informations précieuses." },
    { q: "Comment accéder aux bonus ?", a: "Les bonus sont inclus automatiquement et gratuitement avec toute réservation effectuée avant la date de lancement. Ils vous seront envoyés en même temps que le guide." },
  ];

  return (
    <main className="min-h-screen relative bg-background">
      {/* Sticky CTA Mobile */}
      <div className="md:hidden fixed bottom-4 left-4 right-4 z-50">
        <button className="w-full btn-premium shadow-2xl py-3 text-lg" onClick={() => document.getElementById("preorder")?.scrollIntoView({ behavior: "smooth" })}>
          Réserver (5 000 XAF)
        </button>
      </div>

      <div className="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-navy/5 to-transparent -z-10 pointer-events-none" />
      
      {/* HERO SECTION */}
      <section className="pt-20 pb-20 px-6 max-w-7xl mx-auto flex flex-col lg:flex-row items-center gap-16 relative">
        <div className="absolute top-[-10%] right-[-5%] w-[50%] h-[50%] rounded-full bg-gold/10 blur-[100px] -z-10" />
        <div className="flex-1 text-center lg:text-left z-10">
          <FadeIn>
            <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-red-100 text-red-700 font-medium text-sm mb-8 border border-red-200">
              <span className="text-lg">🔥</span> PRÉCOMMANDE OUVERTE
            </div>
          </FadeIn>
          
          <FadeIn delay={0.1}>
            <h1 className="text-4xl md:text-5xl lg:text-6xl font-poppins font-bold text-navy leading-tight mb-6">
              Le Guide Ultime des Cabinets RH et Agences de Recrutement au Cameroun
            </h1>
          </FadeIn>
          
          <FadeIn delay={0.2}>
            <div className="space-y-3 text-lg md:text-xl text-slate-600 mb-10 max-w-2xl mx-auto lg:mx-0">
              <p className="flex items-center justify-center lg:justify-start gap-3">
                <CheckCircle2 className="text-gold w-6 h-6 shrink-0" /> Plus de 30 cabinets RH analysés.
              </p>
              <p className="flex items-center justify-center lg:justify-start gap-3">
                <CheckCircle2 className="text-gold w-6 h-6 shrink-0" /> Plus de 50 entreprises qui recrutent régulièrement.
              </p>
              <p className="flex items-center justify-center lg:justify-start gap-3">
                <CheckCircle2 className="text-gold w-6 h-6 shrink-0" /> Des bonus exclusifs pour accélérer votre carrière.
              </p>
            </div>
          </FadeIn>
          
          <FadeIn delay={0.3}>
            <div className="glass rounded-3xl p-6 md:p-8 mb-10 max-w-lg mx-auto lg:mx-0 border border-gold/30 relative overflow-hidden">
              <div className="absolute top-0 right-0 w-32 h-32 bg-gold/10 rounded-full blur-2xl -mr-10 -mt-10" />
              <p className="text-sm text-slate-500 uppercase tracking-wider font-bold mb-4 text-center lg:text-left">
                Lancement le 25 Août 2026
              </p>
              <div className="flex justify-center lg:justify-start gap-3 md:gap-4 mb-6">
                {[
                  { label: "Jours", value: timeLeft.days },
                  { label: "Heures", value: timeLeft.hours },
                  { label: "Min", value: timeLeft.minutes },
                  { label: "Sec", value: timeLeft.seconds }
                ].map((time, idx) => (
                  <div key={idx} className="flex flex-col items-center">
                    <div className="bg-navy text-white text-2xl md:text-4xl font-bold rounded-xl w-14 h-14 md:w-16 md:h-16 flex items-center justify-center shadow-lg border-b-4 border-blue-900">
                      {time.value.toString().padStart(2, '0')}
                    </div>
                    <span className="text-xs text-slate-500 mt-2 font-medium uppercase tracking-wider">{time.label}</span>
                  </div>
                ))}
              </div>
              
              <div className="flex items-end justify-center lg:justify-start gap-4 mb-8">
                <div className="flex flex-col">
                  <span className="text-sm text-slate-500 line-through">Prix normal 15 000 XAF</span>
                  <span className="text-4xl md:text-5xl font-poppins font-bold text-navy">5 000 XAF</span>
                </div>
                <div className="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-md mb-2 border border-green-200">
                  -66%
                </div>
              </div>
              
              <button onClick={() => document.getElementById("preorder")?.scrollIntoView({ behavior: "smooth" })} className="w-full btn-premium flex items-center justify-center gap-2 text-lg">
                Je réserve ma copie <ArrowRight className="w-5 h-5" />
              </button>
              
              <div className="mt-4 flex justify-center gap-6 text-xs text-slate-500 font-medium">
                <span className="flex items-center gap-1"><Lock className="w-4 h-4 text-gold" /> Paiement sécurisé</span>
                <span className="flex items-center gap-1"><Download className="w-4 h-4 text-gold" /> Accès digital</span>
              </div>
            </div>
          </FadeIn>
          
          <FadeIn delay={0.4}>
            <a href="#contenu" className="text-navy font-semibold flex items-center justify-center lg:justify-start gap-2 hover:text-gold transition-colors">
              Voir ce que contient le guide <ChevronDown className="w-5 h-5 animate-bounce" />
            </a>
          </FadeIn>
        </div>
        
        <div className="flex-1 w-full max-w-lg lg:max-w-none relative mt-10 lg:mt-0">
          <FadeIn delay={0.2} className="relative z-10">
            {/* Ebook Mockup */}
            <motion.div 
              initial={{ rotateY: -5, rotateX: 2 }}
              animate={{ rotateY: [-2, 2, -2], y: [-5, 5, -5] }}
              transition={{ repeat: Infinity, duration: 6, ease: "easeInOut" }}
              className="relative aspect-[2/3] w-full max-w-[320px] mx-auto rounded-r-2xl rounded-l-md shadow-[0_20px_50px_rgba(7,26,82,0.5)] overflow-hidden border-l-[12px] border-l-slate-900 border-y border-r border-gold/20"
            >
              <Image 
                src="/ebook-cover.png" 
                alt="Le Guide Ultime des Cabinets RH au Cameroun" 
                fill 
                className="object-cover" 
                priority
              />
            </motion.div>
          </FadeIn>
        </div>
      </section>

      {/* SOCIAL PROOF */}
      <section className="bg-navy py-16 px-6 relative overflow-hidden border-y border-gold/20">
        <div className="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-[0.03]" />
        <div className="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-4 relative z-10">
          {[
            { number: "30+", label: "Cabinets RH analysés" },
            { number: "50+", label: "Entreprises qui recrutent" },
            { number: "100+", label: "Contacts professionnels" },
            { number: "4", label: "Bonus exclusifs" }
          ].map((stat, idx) => (
            <FadeIn key={idx} delay={idx * 0.1} className="text-center">
              <div className="text-4xl md:text-5xl font-poppins font-bold text-gradient mb-2">{stat.number}</div>
              <div className="text-blue-200 text-sm md:text-base font-medium">{stat.label}</div>
            </FadeIn>
          ))}
        </div>
      </section>

      {/* WHY THIS GUIDE */}
      <section className="py-24 px-6 relative bg-slate-50">
        <div className="max-w-3xl mx-auto text-center">
          <FadeIn>
            <h2 className="text-3xl md:text-4xl font-poppins font-bold text-navy mb-8">Pourquoi ce guide est-il <span className="text-gold">essentiel</span> ?</h2>
          </FadeIn>
          <div className="space-y-6 text-lg text-slate-600 leading-relaxed">
            <FadeIn delay={0.1}>
              <p>Trouver des informations fiables sur les cabinets RH et les entreprises qui recrutent au Cameroun est un véritable parcours du combattant. Les offres sont souvent cachées, les contacts inaccessibles, et les candidats se retrouvent perdus.</p>
            </FadeIn>
            <FadeIn delay={0.2}>
              <p>C'est pourquoi nous avons créé ce guide. Après des mois d'analyse, nous avons compilé la base de données la plus complète pour vous donner un avantage concurrentiel décisif.</p>
            </FadeIn>
            <FadeIn delay={0.3}>
              <p className="font-semibold text-navy text-xl border-l-4 border-gold pl-6 text-left italic">
                "Que vous soyez un jeune diplômé ou un professionnel en quête de nouvelles opportunités, ce guide est la clé pour accéder au marché caché de l'emploi au Cameroun."
              </p>
            </FadeIn>
          </div>
        </div>
      </section>

      {/* WHAT'S INSIDE */}
      <section id="contenu" className="py-24 px-6 relative">
        <div className="max-w-7xl mx-auto">
          <FadeIn className="text-center mb-16">
            <h2 className="text-3xl md:text-5xl font-poppins font-bold text-navy mb-4">Que contient le guide ?</h2>
            <p className="text-slate-500 text-lg">Une mine d'or d'informations structurées pour votre recherche d'emploi.</p>
          </FadeIn>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {whatsInside.map((item, idx) => (
              <FadeIn key={idx} delay={idx * 0.1}>
                <motion.div 
                  whileHover={{ y: -5, scale: 1.02 }}
                  className="glass p-8 rounded-2xl flex flex-col items-center text-center gap-4 h-full border border-slate-200 hover:border-gold/50 transition-all cursor-default shadow-sm hover:shadow-xl"
                >
                  <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-navy to-blue-800 text-gold flex items-center justify-center shadow-lg">
                    {item.icon}
                  </div>
                  <h3 className="font-poppins font-bold text-navy text-lg">{item.title}</h3>
                </motion.div>
              </FadeIn>
            ))}
          </div>
        </div>
      </section>

      {/* BONUSES */}
      <section className="py-24 px-6 bg-navy relative overflow-hidden">
        <div className="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] rounded-full bg-gold/5 blur-[120px] -z-10" />
        <div className="max-w-7xl mx-auto">
          <FadeIn className="text-center mb-16">
            <div className="inline-block px-4 py-1 rounded-full bg-gold/20 text-gold font-semibold text-sm mb-4 border border-gold/30">CADEAUX EXCLUSIFS</div>
            <h2 className="text-3xl md:text-5xl font-poppins font-bold text-white mb-4">4 Bonus Premium Inclus</h2>
            <p className="text-blue-200 text-lg max-w-2xl mx-auto">Commandez pendant le lancement et recevez ces ressources inestimables gratuitement.</p>
          </FadeIn>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {bonuses.map((bonus, idx) => (
              <FadeIn key={idx} delay={idx * 0.1}>
                <div className="bg-slate-900/50 backdrop-blur-md rounded-3xl p-8 border border-white/10 hover:border-gold/40 transition-colors h-full flex flex-col relative overflow-hidden group">
                  <div className="absolute top-0 right-0 bg-gold text-navy font-bold text-sm px-4 py-1 rounded-bl-xl shadow-md z-10">
                    Valeur : {bonus.value}
                  </div>
                  <div className="text-5xl mb-6 relative z-10">{bonus.emoji}</div>
                  <h3 className="text-2xl font-poppins font-bold text-white mb-4 relative z-10 group-hover:text-gold transition-colors">{bonus.title}</h3>
                  {bonus.details && (
                    <ul className="mt-auto space-y-3 relative z-10">
                      {bonus.details.map((detail, i) => (
                        <li key={i} className="flex items-start gap-2 text-slate-300 text-sm">
                          <CheckCircle2 className="w-5 h-5 text-gold shrink-0" />
                          <span>{detail}</span>
                        </li>
                      ))}
                    </ul>
                  )}
                  <div className="absolute -bottom-10 -right-10 w-40 h-40 bg-gold/5 rounded-full blur-3xl group-hover:bg-gold/10 transition-colors" />
                </div>
              </FadeIn>
            ))}
          </div>
        </div>
      </section>

      {/* TOTAL VALUE */}
      <section className="py-24 px-6 bg-slate-50 relative">
        <div className="max-w-4xl mx-auto">
          <FadeIn>
            <div className="glass rounded-3xl p-8 md:p-12 border-2 border-gold/30 shadow-2xl relative overflow-hidden bg-white">
              <div className="absolute top-0 right-0 w-64 h-64 bg-gold/10 rounded-full blur-3xl" />
              <h2 className="text-3xl md:text-4xl font-poppins font-bold text-navy mb-10 text-center">Récapitulatif de l'offre</h2>
              
              <div className="space-y-6 mb-10">
                <div className="flex justify-between items-center pb-6 border-b border-slate-100">
                  <span className="text-xl font-medium text-slate-700">Guide Premium</span>
                  <span className="text-xl font-bold text-navy">15 000 XAF</span>
                </div>
                <div className="flex justify-between items-center pb-6 border-b border-slate-100">
                  <span className="text-xl font-medium text-slate-700">Les 4 Bonus Premium</span>
                  <span className="text-xl font-bold text-navy">50 000 XAF</span>
                </div>
                <div className="flex justify-between items-center py-4 bg-slate-50 px-6 rounded-xl">
                  <span className="text-xl font-bold text-slate-500">Valeur Totale</span>
                  <span className="text-2xl font-bold text-slate-500 line-through decoration-red-500 decoration-2">65 000 XAF</span>
                </div>
              </div>

              <div className="bg-gradient-to-br from-navy to-blue-900 rounded-2xl p-8 text-center text-white relative shadow-xl overflow-hidden">
                <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-gold via-yellow-200 to-gold" />
                <p className="text-blue-200 font-medium mb-2">Aujourd'hui au tarif de lancement</p>
                <div className="text-5xl md:text-6xl font-poppins font-bold text-gold mb-4">5 000 XAF</div>
                <p className="text-lg font-bold text-white bg-white/20 inline-block px-6 py-2 rounded-full mb-6 border border-white/30">
                  Vous économisez 60 000 XAF !
                </p>
                <button onClick={() => document.getElementById("preorder")?.scrollIntoView({ behavior: "smooth" })} className="w-full btn-premium py-4 text-xl">
                  Je réserve maintenant
                </button>
              </div>
            </div>
          </FadeIn>
        </div>
      </section>

      {/* WHY BUY NOW */}
      <section className="py-24 px-6 max-w-7xl mx-auto">
        <FadeIn className="text-center mb-16">
          <h2 className="text-3xl md:text-5xl font-poppins font-bold text-navy mb-4">Pourquoi précommander ?</h2>
        </FadeIn>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {[
            { title: "Tarif Limité", desc: "Le prix passera à 15 000 XAF le 25 Août." },
            { title: "Réservation Immédiate", desc: "Soyez assuré d'avoir votre copie dès le jour J." },
            { title: "Bonus Inclus", desc: "Les 4 bonus ne seront plus garantis après le lancement." },
            { title: "Avance Compétitive", desc: "Faites partie des premiers à exploiter ces informations." }
          ].map((item, idx) => (
            <FadeIn key={idx} delay={idx * 0.1}>
              <div className="bg-white p-8 rounded-2xl shadow-lg border border-slate-100 h-full hover:-translate-y-2 transition-transform duration-300">
                <div className="w-12 h-12 bg-gold/20 text-gold rounded-full flex items-center justify-center font-bold mb-6">
                  {idx + 1}
                </div>
                <h3 className="font-poppins font-bold text-navy text-xl mb-3">{item.title}</h3>
                <p className="text-slate-600">{item.desc}</p>
              </div>
            </FadeIn>
          ))}
        </div>
      </section>

      {/* FAQ */}
      <section className="py-24 px-6 max-w-4xl mx-auto">
        <FadeIn className="text-center mb-16">
          <h2 className="text-3xl md:text-5xl font-poppins font-bold text-navy mb-4">Questions Fréquentes</h2>
        </FadeIn>
        
        <div className="space-y-4">
          {faqs.map((faq, idx) => (
            <FadeIn key={idx} delay={idx * 0.05}>
              <div 
                className="bg-white border border-slate-200 rounded-xl overflow-hidden cursor-pointer hover:border-gold/50 transition-colors"
                onClick={() => setOpenFaq(openFaq === idx ? null : idx)}
              >
                <div className="p-6 flex justify-between items-center">
                  <h3 className="font-bold text-navy text-lg">{faq.q}</h3>
                  <ChevronDown className={`w-5 h-5 text-slate-400 transition-transform ${openFaq === idx ? 'rotate-180' : ''}`} />
                </div>
                <motion.div 
                  initial={false}
                  animate={{ height: openFaq === idx ? 'auto' : 0, opacity: openFaq === idx ? 1 : 0 }}
                  className="overflow-hidden"
                >
                  <div className="p-6 pt-0 text-slate-600 border-t border-slate-100 mt-2">
                    {faq.a}
                  </div>
                </motion.div>
              </div>
            </FadeIn>
          ))}
        </div>
      </section>

      {/* PREORDER FORM */}
      <section id="preorder" className="py-24 px-6 relative bg-navy overflow-hidden">
        <div className="absolute top-0 right-0 w-[80%] h-[100%] rounded-full bg-blue-600/10 blur-[150px] -z-10" />
        <div className="max-w-5xl mx-auto flex flex-col lg:flex-row gap-16 items-center">
          <div className="flex-1 text-white">
            <FadeIn>
              <h2 className="text-3xl md:text-5xl font-poppins font-bold mb-6">Prêt à propulser votre carrière ?</h2>
              <p className="text-blue-200 text-lg mb-8">Remplissez le formulaire pour sécuriser votre exemplaire au tarif de lancement avec tous les bonus.</p>
              
              <ul className="space-y-4 mb-8">
                <li className="flex items-center gap-3">
                  <CheckCircle2 className="text-gold w-6 h-6" /> <span className="font-medium">Guide Complet (PDF)</span>
                </li>
                <li className="flex items-center gap-3">
                  <CheckCircle2 className="text-gold w-6 h-6" /> <span className="font-medium">4 Bonus Exclusifs</span>
                </li>
                <li className="flex items-center gap-3">
                  <CheckCircle2 className="text-gold w-6 h-6" /> <span className="font-medium">Livraison le 25 Août 2026</span>
                </li>
              </ul>
            </FadeIn>
          </div>
          
          <div className="flex-1 w-full">
            <FadeIn delay={0.2}>
              <div className="bg-white rounded-3xl p-8 shadow-2xl relative">
                <h3 className="text-2xl font-poppins font-bold text-navy mb-6">Détails de réservation</h3>
                
                {isSuccess ? (
                  <div className="text-center py-8">
                    <div className="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                      <CheckCircle2 className="w-8 h-8" />
                    </div>
                    <h4 className="text-xl font-bold text-navy mb-2">Réservation Confirmée !</h4>
                    <p className="text-slate-600">
                      Merci pour votre réservation. Vous recevrez un email le 25 Août 2026 pour télécharger le guide et vos bonus.
                    </p>
                  </div>
                ) : (
                  <form className="space-y-4" onSubmit={handleSubmit}>
                    <div>
                      <label className="block text-sm font-medium text-slate-700 mb-1">Nom complet *</label>
                      <input type="text" value={formData.fullName} onChange={e => setFormData({...formData, fullName: e.target.value})} className="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-gold focus:border-gold outline-none transition-all" placeholder="Jean Dupont" required />
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-slate-700 mb-1">Adresse Email *</label>
                      <input type="email" value={formData.email} onChange={e => setFormData({...formData, email: e.target.value})} onBlur={handlePartialSave} className="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-gold focus:border-gold outline-none transition-all" placeholder="jean@exemple.com" required />
                    </div>
                    
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Numéro WhatsApp *</label>
                        <input type="tel" value={formData.whatsapp} onChange={e => setFormData({...formData, whatsapp: e.target.value})} onBlur={handlePartialSave} className="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-gold focus:border-gold outline-none transition-all" placeholder="+237..." required />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Ville *</label>
                        <input type="text" value={formData.city} onChange={e => setFormData({...formData, city: e.target.value})} className="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-gold focus:border-gold outline-none transition-all" placeholder="Douala" required />
                      </div>
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-slate-700 mb-1">Profession actuelle *</label>
                      <input type="text" value={formData.profession} onChange={e => setFormData({...formData, profession: e.target.value})} className="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-gold focus:border-gold outline-none transition-all" placeholder="Ex: Chef de projet" required />
                    </div>
                    
                    <div className="flex items-start gap-3 mt-6 mb-8 bg-slate-50 p-4 rounded-xl">
                      <input type="checkbox" id="updates" className="mt-1 w-4 h-4 text-gold border-gray-300 rounded focus:ring-gold" defaultChecked />
                      <label htmlFor="updates" className="text-sm text-slate-600 cursor-pointer">
                        Je souhaite recevoir des mises à jour concernant le lancement et des conseils carrières.
                      </label>
                    </div>
                    
                    <button type="submit" disabled={isSubmitting} className="w-full btn-premium py-4 text-lg disabled:opacity-70">
                      {isSubmitting ? "Réservation en cours..." : "Je réserve ma copie (5 000 XAF)"}
                    </button>
                    
                    <p className="text-center text-xs text-slate-500 mt-4 flex items-center justify-center gap-1">
                      <Lock className="w-3 h-3" /> Vos informations restent strictement confidentielles.
                    </p>
                  </form>
                )}
              </div>
            </FadeIn>
          </div>
        </div>
      </section>

      {/* FINAL CTA & FOOTER */}
      <footer className="bg-slate-950 text-slate-400 py-12 px-6 border-t border-white/10">
        <div className="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
          <div className="text-xl font-poppins font-bold text-white flex items-center gap-2">
            <div className="w-8 h-8 bg-gold rounded-lg flex items-center justify-center text-navy">H</div>
            HireMe
          </div>
          
          <div className="flex gap-6 text-sm">
            <a href="/politique-confidentialite" className="hover:text-gold transition-colors">Politique de confidentialité</a>
            <a href="#" className="hover:text-gold transition-colors">Conditions générales</a>
            <a href="#" className="hover:text-gold transition-colors">Contact</a>
          </div>
          
          <div className="text-sm">
            &copy; 2026 HireMe. Tous droits réservés.
          </div>
        </div>
      </footer>
    </main>
  );
}
