import type { MealSlotCode } from '@meal/bff-client';

export const MEAL_SLOT_CODES: MealSlotCode[] = [
  'BREAKFAST',
  'SECOND_BREAKFAST',
  'LUNCH',
  'SNACK',
  'DINNER',
  'LATE_DINNER',
];

export const MEAL_SLOT_LABELS: Record<MealSlotCode, string> = {
  BREAKFAST: 'Завтрак',
  SECOND_BREAKFAST: 'Второй завтрак',
  LUNCH: 'Обед',
  SNACK: 'Полдник',
  DINNER: 'Ужин',
  LATE_DINNER: 'Поздний ужин',
};
