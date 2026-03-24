import { defineAsyncComponent } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '../views/HomeView.vue';
import LoginView from '../views/LoginView.vue';
import RegisterView from '../views/RegisterView.vue';

const RecipesLibrary = defineAsyncComponent(() => import('mf_recipes/Library'));
const RecipesImport = defineAsyncComponent(() => import('mf_recipes/ImportView'));
const RecipeDetail = defineAsyncComponent(() => import('mf_recipes/RecipeDetail'));
const RecipeForm = defineAsyncComponent(() => import('mf_recipes/RecipeForm'));
const PlannerPage = defineAsyncComponent(() => import('mf_planner/PlannerPage'));
const ShoppingPage = defineAsyncComponent(() => import('mf_shopping/ShoppingPage'));

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/login', name: 'login', component: LoginView },
    { path: '/register', name: 'register', component: RegisterView },
    { path: '/recipes', name: 'recipes', component: RecipesLibrary },
    { path: '/recipes/import', name: 'recipes-import', component: RecipesImport },
    { path: '/recipes/new', name: 'recipe-new', component: RecipeForm },
    { path: '/recipes/:id/edit', name: 'recipe-edit', component: RecipeForm },
    { path: '/recipes/:id', name: 'recipe-detail', component: RecipeDetail },
    { path: '/planner', name: 'planner', component: PlannerPage },
    { path: '/shopping/:listId', name: 'shopping', component: ShoppingPage },
  ],
});
