"use client";

export default function ExportButton({ data, filename }: { data: any[], filename: string }) {
  const exportCSV = () => {
    if (!data.length) return alert("Aucune donnée à exporter.");
    
    const headers = Object.keys(data[0]).join(",");
    const rows = data.map(obj => 
      Object.values(obj).map(value => `"${value}"`).join(",")
    ).join("\n");
    
    const csvContent = "data:text/csv;charset=utf-8," + headers + "\n" + rows;
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
  };

  return (
    <button 
      onClick={exportCSV}
      className="bg-navy hover:bg-blue-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
    >
      Exporter CSV
    </button>
  );
}
