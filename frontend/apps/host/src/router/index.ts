import { createRouter, createWebHistory } from 'vue-router';
import { useSession } from '../composables/useSession';
import HomeView from '../views/HomeView.vue';
import LoginView from '../views/LoginView.vue';
import RegisterView from '../views/RegisterView.vue';
import ProfileView from '../views/ProfileView.vue';
import { APP_ROUTES } from '../app/routes';
import { routeRequiresAuth } from './route-guard';
import { isDemoMode } from '../demo/mode';

const RecipesLibrary = () => import('mf_recipes/Library');
const RecipesImport = () => import('mf_recipes/ImportView');
const RecipeDetail = () => import('mf_recipes/RecipeDetail');
const RecipeForm = () => import('mf_recipes/RecipeForm');
const PlannerPage = () => import('mf_planner/PlannerPage');
const ShoppingPage = () => import('mf_shopping/ShoppingPage');

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: APP_ROUTES.HOME, name: 'home', component: HomeView },
    { path: APP_ROUTES.LOGIN, name: 'login', component: LoginView },
    { path: APP_ROUTES.REGISTER, name: 'register', component: RegisterView },
    { path: APP_ROUTES.PROFILE, name: 'profile', component: ProfileView, meta: { requiresAuth: true } },
    { path: APP_ROUTES.RECIPES, name: 'recipes', component: RecipesLibrary, meta: { requiresAuth: true } },
    { path: APP_ROUTES.RECIPES_IMPORT, name: 'recipes-import', component: RecipesImport, meta: { requiresAuth: true } },
    { path: APP_ROUTES.RECIPES_NEW, name: 'recipe-new', component: RecipeForm, meta: { requiresAuth: true } },
    { path: APP_ROUTES.RECIPE_EDIT, name: 'recipe-edit', component: RecipeForm, meta: { requiresAuth: true } },
    { path: APP_ROUTES.RECIPE_DETAIL, name: 'recipe-detail', component: RecipeDetail, meta: { requiresAuth: true } },
    { path: APP_ROUTES.PLANNER, name: 'planner', component: PlannerPage, meta: { requiresAuth: true } },
    { path: APP_ROUTES.SHOPPING, name: 'shopping', component: ShoppingPage, meta: { requiresAuth: true } },
  ],
});

router.beforeEach(async (to) => {
  if (isDemoMode()) {
    if (to.name === 'login' || to.name === 'register' || to.name === 'profile') {
      return { name: 'recipes' };
    }
    return true;
  }

  if (!routeRequiresAuth(to)) {
    return true;
  }
  const { isLoggedIn, refreshSession } = useSession();
  if (isLoggedIn.value === null) {
    await refreshSession();
  }
  if (!isLoggedIn.value) {
    return { name: 'login', query: { ...to.query, redirect: to.fullPath } };
  }
  return true;
});

export default router;
