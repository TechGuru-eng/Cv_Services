module.exports = {
  content: ["./*.{html,js,php}"],
  safelist: [
    { pattern: /^w-\d+\/\d+$/ },
    { pattern: /^bg-cv-/ },
    { pattern: /^text-cv-/ },
    { pattern: /^border-cv-/ },
    { pattern: /^gap-/ },
    { pattern: /^space-y-/ },
    { pattern: /^text-\[/ },
    { pattern: /^grid-cols-/ },
    { pattern: /^col-span-/ },
    // Layout
    'flex', 'flex-col', 'flex-1', 'flex-wrap', 'items-center', 'items-start', 'items-baseline',
    'justify-between', 'justify-center', 'justify-start',
    'w-full', 'w-1/3', 'w-2/3', 'w-[30%]', 'w-20', 'w-24', 'w-28', 'h-20', 'h-24', 'h-28',
    'h-full', 'w-1', 'h-4', 'w-2.5', 'h-2.5', 'w-5', 'h-5', 'w-3', 'h-3', 'h-1.5',
    'min-h-full', 'overflow-hidden', 'relative', 'absolute', 'shrink-0',
    // Grid
    'grid', 'grid-cols-2', 'grid-cols-4', 'col-span-1', 'col-span-3',
    // Spacing
    'p-4', 'p-6', 'p-8', 'p-10', 'p-2', 'p-2.5', 'p-1',
    'px-2', 'px-3', 'px-4', 'px-2.5',
    'py-0.5', 'py-1', 'py-2',
    'mb-1', 'mb-1.5', 'mb-2', 'mb-2.5', 'mb-3', 'mb-4', 'mb-5', 'mb-6', 'mb-7', 'mb-8',
    'mt-0.5', 'mt-1', 'mt-2', 'mt-5', 'mt-6',
    'mr-1', 'mr-1.5', 'mr-1.5', 'mr-1.5',
    'pb-1', 'pb-6', 'pt-2', 'pt-3',
    'gap-1', 'gap-1.5', 'gap-2', 'gap-4', 'gap-6', 'gap-8', 'gap-x-3', 'gap-x-4', 'gap-y-1', 'gap-y-1.5',
    'space-y-6', 'space-y-5', 'space-y-4',
    // Typography
    'text-[10px]', 'text-[11px]', 'text-[12px]', 'text-[13px]', 'text-sm', 'text-xs',
    'text-2xl', 'text-3xl', 'text-4xl', 'text-xl', 'text-lg',
    'font-bold', 'font-black', 'font-extrabold', 'font-semibold', 'font-medium',
    'uppercase', 'tracking-wider', 'tracking-widest', 'tracking-tight', 'tracking-tighter',
    'leading-none', 'leading-tight', 'leading-snug', 'leading-relaxed',
    'text-center', 'text-justify', 'text-left', 'truncate', 'inline-block', 'inline',
    // Colors - Text
    'text-white', 'text-slate-100', 'text-slate-200', 'text-slate-300', 'text-slate-400',
    'text-slate-500', 'text-slate-600', 'text-slate-700', 'text-slate-800', 'text-slate-900',
    // Colors - BG
    'bg-white', 'bg-slate-50', 'bg-slate-100', 'bg-slate-800', 'bg-slate-950', 'bg-black',
    'bg-white/10', 'bg-black/20', 'bg-cv-primary', 'bg-cv-dark', 'bg-cv-gradient',
    // Borders
    'border', 'border-2', 'border-t', 'border-b', 'border-b-2', 'border-l', 'border-l-2',
    'border-slate-100', 'border-slate-200', 'border-white', 'border-white/10',
    'border-cv-primary', 'border-slate-900',
    'rounded', 'rounded-full', 'rounded-lg', 'rounded-xl', 'rounded-2xl', 'rounded-md',
    'pl-4', 'pr-6',
    // Misc
    'shadow-md', 'shadow-xl', 'shadow-lg', 'grayscale', 'select-none',
    'overflow-hidden', 'overflow-x-hidden',
    '-left-[6px]', 'top-1.5', 'ring-4', 'ring-white',
    'fill-current',
    'cv-name', 'cv-paper',
    // Special sizing
    'w-[30%]', 'h-1.5',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
      },
      colors: {
        'cv-primary': 'var(--cv-primary)',
        'cv-dark': 'var(--cv-dark)',
        'cv-light': 'var(--cv-light)',
      }
    },
  },
  plugins: [],
}
