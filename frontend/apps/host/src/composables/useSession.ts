import { bffErrorFromResponse } from '@meal/bff-client';
import { ref } from 'vue';
import { getBff } from '../bff';

/** null = ещё не проверяли; true/false после refresh или login/logout */
const isLoggedIn = ref<boolean | null>(null);

export function useSession() {
  async function refreshSession(): Promise<void> {
    const bff = getBff();
    try {
      const r = await bff.fetch('/recipes?limit=1');
      isLoggedIn.value = r.ok;
    } catch {
      isLoggedIn.value = false;
    }
  }

  async function login(email: string, password: string): Promise<void> {
    const bff = getBff();
    const r = await bff.fetch('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    if (!r.ok) {
      const err = await bffErrorFromResponse(r);
      throw new Error(err.message);
    }
    isLoggedIn.value = true;
  }

  async function register(email: string, password: string): Promise<void> {
    const bff = getBff();
    const r = await bff.fetch('/auth/register', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    if (!r.ok) {
      const err = await bffErrorFromResponse(r);
      throw new Error(err.message);
    }
  }

  async function logout(): Promise<void> {
    const bff = getBff();
    try {
      await bff.fetch('/auth/logout', { method: 'POST' });
    } catch {
      /* сеть может упасть — всё равно сбрасываем локально */
    }
    isLoggedIn.value = false;
  }

  return {
    isLoggedIn,
    refreshSession,
    login,
    register,
    logout,
  };
}
