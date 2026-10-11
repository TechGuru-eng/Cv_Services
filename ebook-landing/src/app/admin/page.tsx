import prisma from "@/lib/prisma";
import ExportButton from "./ExportButton";

export const dynamic = 'force-dynamic';

export default async function AdminDashboard() {
  const subscribers = await prisma.subscriber.findMany({
    orderBy: { createdAt: 'desc' }
  });

  const bouncedVisitors = await prisma.visitor.findMany({
    where: { 
      converted: false,
      OR: [
        { email: { not: null } },
        { whatsapp: { not: null } }
      ]
    },
    orderBy: { updatedAt: 'desc' }
  });

  const anonymousHits = await prisma.visitor.count({
    where: { converted: false, email: null, whatsapp: null }
  });

  return (
    <main className="min-h-screen bg-slate-50 p-8 font-inter text-slate-800">
      <div className="max-w-7xl mx-auto">
        <h1 className="text-3xl font-poppins font-bold text-navy mb-8">Tableau de Bord Ebook</h1>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
          <div className="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h3 className="text-slate-500 font-medium mb-2">Précommandes (Total)</h3>
            <div className="text-4xl font-bold text-navy">{subscribers.length}</div>
          </div>
          <div className="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h3 className="text-slate-500 font-medium mb-2">Paniers Abandonnés (Contactables)</h3>
            <div className="text-4xl font-bold text-orange-500">{bouncedVisitors.length}</div>
          </div>
          <div className="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h3 className="text-slate-500 font-medium mb-2">Visiteurs Anonymes (Bounces)</h3>
            <div className="text-4xl font-bold text-slate-400">{anonymousHits}</div>
          </div>
        </div>

        {/* Subscribers Section */}
        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 mb-12 overflow-hidden">
          <div className="p-6 border-b border-slate-100 flex justify-between items-center">
            <h2 className="text-xl font-bold text-navy">Précommandes Validées</h2>
            <ExportButton data={subscribers} filename="precommandes.csv" />
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-left">
              <thead className="bg-slate-50 text-slate-500 text-sm">
                <tr>
                  <th className="p-4 font-medium">Nom</th>
                  <th className="p-4 font-medium">Email</th>
                  <th className="p-4 font-medium">WhatsApp</th>
                  <th className="p-4 font-medium">Ville</th>
                  <th className="p-4 font-medium">Profession</th>
                  <th className="p-4 font-medium">Date</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {subscribers.map(sub => (
                  <tr key={sub.id} className="hover:bg-slate-50">
                    <td className="p-4">{sub.fullName}</td>
                    <td className="p-4">{sub.email}</td>
                    <td className="p-4">{sub.whatsapp}</td>
                    <td className="p-4">{sub.city}</td>
                    <td className="p-4">{sub.profession}</td>
                    <td className="p-4 text-sm text-slate-500">{new Date(sub.createdAt).toLocaleDateString()}</td>
                  </tr>
                ))}
                {subscribers.length === 0 && (
                  <tr>
                    <td colSpan={6} className="p-8 text-center text-slate-500">Aucune précommande pour le moment.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Retargeting Section */}
        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
          <div className="p-6 border-b border-slate-100 flex justify-between items-center">
            <div>
              <h2 className="text-xl font-bold text-navy">Retargeting (Formulaires Abandonnés)</h2>
              <p className="text-sm text-slate-500">Visiteurs ayant commencé à remplir le formulaire sans le valider.</p>
            </div>
            <ExportButton data={bouncedVisitors} filename="retargeting.csv" />
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-left">
              <thead className="bg-slate-50 text-slate-500 text-sm">
                <tr>
                  <th className="p-4 font-medium">Email Capturé</th>
                  <th className="p-4 font-medium">WhatsApp Capturé</th>
                  <th className="p-4 font-medium">Dernière Activité</th>
                  <th className="p-4 font-medium">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {bouncedVisitors.map(visitor => (
                  <tr key={visitor.id} className="hover:bg-slate-50">
                    <td className="p-4">{visitor.email || <span className="text-slate-300">Non renseigné</span>}</td>
                    <td className="p-4">{visitor.whatsapp || <span className="text-slate-300">Non renseigné</span>}</td>
                    <td className="p-4 text-sm text-slate-500">{new Date(visitor.updatedAt).toLocaleString()}</td>
                    <td className="p-4">
                      {visitor.whatsapp && (
                        <a 
                          href={`https://wa.me/${visitor.whatsapp.replace(/\D/g,'')}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-green-600 hover:text-green-700 text-sm font-medium"
                        >
                          Relancer via WhatsApp
                        </a>
                      )}
                    </td>
                  </tr>
                ))}
                {bouncedVisitors.length === 0 && (
                  <tr>
                    <td colSpan={4} className="p-8 text-center text-slate-500">Aucun visiteur à relancer pour le moment.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  );
}
