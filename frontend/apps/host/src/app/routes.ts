export const APP_ROUTES = {
  HOME: '/',
  LOGIN: '/login',
  REGISTER: '/register',
  RECIPES: '/recipes',
  RECIPES_IMPORT: '/recipes/import',
  RECIPES_NEW: '/recipes/new',
  RECIPE_EDIT: '/recipes/:id/edit',
  RECIPE_DETAIL: '/recipes/:id',
  PLANNER: '/planner',
  SHOPPING: '/shopping/:listId',
} as const;
