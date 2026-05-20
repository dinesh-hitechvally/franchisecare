// Default category colors - used as fallbacks when category doesn't have a predefined color
export const INVENTORY_CATEGORY_COLORS: Record<string, string> = {
  office: 'bg-gray-100 text-gray-700',
  shampoo: 'bg-blue-100 text-blue-700',
  treats: 'bg-green-100 text-green-700',
  uniforms: 'bg-purple-100 text-purple-700',
  marketing: 'bg-orange-100 text-orange-700',
  General: 'bg-slate-100 text-slate-700',
}

// Default category labels - used as fallbacks when category doesn't have a predefined label
export const INVENTORY_CATEGORY_LABELS: Record<string, string> = {
  office: 'Office Supplies',
  shampoo: 'Shampoo',
  treats: 'Treats',
  uniforms: 'Uniforms',
  marketing: 'Marketing',
  General: 'General',
}

// Color palette for dynamically generated categories
const DYNAMIC_COLORS = [
  'bg-cyan-100 text-cyan-700',
  'bg-teal-100 text-teal-700',
  'bg-indigo-100 text-indigo-700',
  'bg-pink-100 text-pink-700',
  'bg-rose-100 text-rose-700',
  'bg-amber-100 text-amber-700',
  'bg-lime-100 text-lime-700',
  'bg-emerald-100 text-emerald-700',
  'bg-sky-100 text-sky-700',
  'bg-violet-100 text-violet-700',
]

// Get color for a category (returns predefined or generates one based on hash)
export function getCategoryColor(category: string): string {
  if (INVENTORY_CATEGORY_COLORS[category]) {
    return INVENTORY_CATEGORY_COLORS[category]
  }
  // Generate a consistent color based on category name hash
  const hash = category.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0)
  return DYNAMIC_COLORS[hash % DYNAMIC_COLORS.length]
}

// Get label for a category (returns predefined or uses category name as-is)
export function getCategoryLabel(category: string): string {
  return INVENTORY_CATEGORY_LABELS[category] || category
}

// Build unique categories from inventory items
export function buildCategoriesFromItems(items: Array<{ category: string }>): Array<{ key: string; label: string; color: string }> {
  const uniqueCategories = [...new Set(items.map(item => item.category))].filter(Boolean)
  
  return uniqueCategories.map(category => ({
    key: category,
    label: getCategoryLabel(category),
    color: getCategoryColor(category),
  }))
}
