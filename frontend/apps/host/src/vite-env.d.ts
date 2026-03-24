/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_BFF_BASE_URL?: string;
}

declare module 'mf_recipes/Entry' {
  import type { DefineComponent } from 'vue';
  const c: DefineComponent;
  export default c;
}

declare module 'mf_planner/Entry' {
  import type { DefineComponent } from 'vue';
  const c: DefineComponent;
  export default c;
}

declare module 'mf_shopping/Entry' {
  import type { DefineComponent } from 'vue';
  const c: DefineComponent;
  export default c;
}
