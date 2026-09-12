import { reactive } from 'vue';
import type { VNode } from 'vue';

export type ShellHeaderLayout = 'default' | 'centeredTitle';

export type ShellHeaderSpec = {
  layout?: ShellHeaderLayout;
  titleLevel?: 1 | 2;
  titleAlign?: 'start' | 'center';
  ariaLabel?: string;
  eyebrow?: string | null;
  title?: string | null;
  /** Host appends global nav in #actions after optional actionsRender */
  showAppNav?: boolean;
  leadingRender?: (() => VNode) | null;
  sublineRender?: (() => VNode) | null;
  actionsRender?: (() => VNode) | null;
};

export type ShellHeaderState = {
  layout: ShellHeaderLayout;
  titleLevel: 1 | 2;
  titleAlign: 'start' | 'center';
  ariaLabel: string;
  eyebrow: string | null;
  title: string | null;
  showAppNav: boolean;
  leadingRender: (() => VNode) | null;
  sublineRender: (() => VNode) | null;
  actionsRender: (() => VNode) | null;
};

const defaults: ShellHeaderState = {
  layout: 'default',
  titleLevel: 2,
  titleAlign: 'start',
  ariaLabel: '',
  eyebrow: null,
  title: null,
  showAppNav: true,
  leadingRender: null,
  sublineRender: null,
  actionsRender: null,
};

/** Singleton shell model: host renders UiAppHeader from this; MFE pages call setShellHeader. */
export const shellHeaderState = reactive<ShellHeaderState>({ ...defaults });

export function setShellHeader(patch: ShellHeaderSpec): void {
  if (patch.layout !== undefined) {
    shellHeaderState.layout = patch.layout;
  }
  if (patch.titleLevel !== undefined) {
    shellHeaderState.titleLevel = patch.titleLevel;
  }
  if (patch.titleAlign !== undefined) {
    shellHeaderState.titleAlign = patch.titleAlign;
  }
  if (patch.ariaLabel !== undefined) {
    shellHeaderState.ariaLabel = patch.ariaLabel;
  }
  if ('eyebrow' in patch) {
    shellHeaderState.eyebrow = patch.eyebrow ?? null;
  }
  if ('title' in patch) {
    shellHeaderState.title = patch.title ?? null;
  }
  if (patch.showAppNav !== undefined) {
    shellHeaderState.showAppNav = patch.showAppNav;
  }
  if ('leadingRender' in patch) {
    shellHeaderState.leadingRender = patch.leadingRender ?? null;
  }
  if ('sublineRender' in patch) {
    shellHeaderState.sublineRender = patch.sublineRender ?? null;
  }
  if ('actionsRender' in patch) {
    shellHeaderState.actionsRender = patch.actionsRender ?? null;
  }
}

export function resetShellHeader(): void {
  Object.assign(shellHeaderState, defaults);
}
