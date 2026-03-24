/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_BFF_BASE_URL?: string;
  readonly VITE_MF_RECIPES_URL?: string;
  readonly VITE_MF_PLANNER_URL?: string;
  readonly VITE_MF_SHOPPING_URL?: string;
}

declare module 'mf_recipes/Entry' {
  import type { DefineComponent } from 'vue';
  const c: DefineComponent;
  export default c;
}

declare module 'mf_recipes/Library' {
  import type { DefineComponent } from 'vue';
  const c: DefineComponent;
  export default c;
}

declare module 'mf_recipes/ImportView' {
  import type { DefineComponent } from 'vue';
  const c: DefineComponent;
  export default c;
}

declare module 'mf_recipes/RecipeDetail' {
  import type { DefineComponent } from 'vue';
  const c: DefineComponent;
  export default c;
}

declare module 'mf_recipes/RecipeForm' {
  import type { DefineComponent } from 'vue';
  const c: DefineComponent;
  export default c;
}

declare module 'mf_planner/Entry' {
  import type { DefineComponent } from 'vue';
  const c: DefineComponent;
  export default c;
}

declare module 'mf_planner/PlannerPage' {
  import type { DefineComponent } from 'vue';
  const c: DefineComponent;
  export default c;
}

declare module 'mf_shopping/Entry' {
  import type { DefineComponent } from 'vue';
  const c: DefineComponent;
  export default c;
}

declare module 'mf_shopping/ShoppingPage' {
  import type { DefineComponent } from 'vue';
  const c: DefineComponent;
  export default c;
}
