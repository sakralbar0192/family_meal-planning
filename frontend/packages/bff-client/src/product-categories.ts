/** Канонические категории продукта для рецептов и списка покупок. */

export const PRODUCT_CATEGORY_OTHER = 'прочее';

export const PRODUCT_CATEGORY_OPTIONS: { value: string; label: string }[] = [
  { value: 'молочные', label: 'Молочные' },
  { value: 'мясо', label: 'Мясо' },
  { value: 'бакалея', label: 'Бакалея' },
  { value: 'овощи', label: 'Овощи' },
  { value: PRODUCT_CATEGORY_OTHER, label: 'Прочее' },
];

const ALIASES: Record<string, string> = {
  dairy: 'молочные',
  meat: 'мясо',
  groceries: 'бакалея',
  vegetables: 'овощи',
  other: PRODUCT_CATEGORY_OTHER,
  beverages: PRODUCT_CATEGORY_OTHER,
  молочные: 'молочные',
  мясо: 'мясо',
  бакалея: 'бакалея',
  овощи: 'овощи',
  прочее: PRODUCT_CATEGORY_OTHER,
  напитки: PRODUCT_CATEGORY_OTHER,
};

const LABELS: Record<string, string> = Object.fromEntries(
  PRODUCT_CATEGORY_OPTIONS.map((o) => [o.value, o.label]),
);

/** Приводит свободную строку категории к каноническому значению. */
export function normalizeProductCategory(raw?: string | null): string {
  const k = (raw ?? '').trim().toLowerCase();
  if (!k) {
    return PRODUCT_CATEGORY_OTHER;
  }
  return ALIASES[k] ?? k;
}

/** Подпись для группировки в списке покупок. */
export function productCategoryLabel(raw?: string | null): string {
  const n = normalizeProductCategory(raw);
  return LABELS[n] ?? (raw?.trim() || 'Прочее');
}
