/**
 * authService.js
 * Handles session, login, registration, and logout connecting to PHP Backend (/api/auth/).
 */

const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000';

export async function checkSession() {
  try {
    const res = await fetch(`${BACKEND_URL}/api/auth/me.php`, {
      method: 'GET',
      credentials: 'include', // sends session cookies
    });
    if (res.ok) {
      const data = await res.json();
      if (data && data.status === 'success' && data.data?.user) {
        return { isAuthenticated: true, user: data.data.user };
      }
    }
  } catch (err) {
    // Backend offline
  }

  // Check fallback local storage if any
  const cachedUser = localStorage.getItem('matchupiq_user');
  if (cachedUser) {
    try {
      return { isAuthenticated: true, user: JSON.parse(cachedUser) };
    } catch {
      localStorage.removeItem('matchupiq_user');
    }
  }

  return { isAuthenticated: false, user: null };
}

export async function loginUser(email, password) {
  try {
    const res = await fetch(`${BACKEND_URL}/api/auth/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ email, password }),
    });

    const data = await res.json().catch(() => null);

    if (res.ok && data?.status === 'success') {
      const user = data.data?.user || { email, full_name: email.split('@')[0] };
      localStorage.setItem('matchupiq_user', JSON.stringify(user));
      return { success: true, user };
    } else {
      return { success: false, message: data?.message || 'Credenciales inválidas' };
    }
  } catch (err) {
    // Fallback simulation for local development if PHP server is down
    if (email && password) {
      const simulatedUser = {
        user_id: 99,
        full_name: email.split('@')[0],
        email: email,
        role: 'user',
      };
      localStorage.setItem('matchupiq_user', JSON.stringify(simulatedUser));
      return { success: true, user: simulatedUser, fallback: true };
    }
    return { success: false, message: 'No se pudo conectar con el servidor.' };
  }
}

export async function registerUser(fullName, email, password) {
  try {
    const res = await fetch(`${BACKEND_URL}/api/auth/register.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ full_name: fullName, email, password }),
    });

    const data = await res.json().catch(() => null);

    if (res.ok && data?.status === 'success') {
      const user = data.data?.user || { full_name: fullName, email };
      localStorage.setItem('matchupiq_user', JSON.stringify(user));
      return { success: true, user };
    } else {
      return { success: false, message: data?.message || 'Error en el registro.' };
    }
  } catch (err) {
    // Fallback simulation
    const simulatedUser = { user_id: 100, full_name: fullName, email, role: 'user' };
    localStorage.setItem('matchupiq_user', JSON.stringify(simulatedUser));
    return { success: true, user: simulatedUser, fallback: true };
  }
}

export async function logoutUser() {
  try {
    await fetch(`${BACKEND_URL}/api/auth/logout.php`, {
      method: 'POST',
      credentials: 'include',
    });
  } catch {
    // Ignore error on network
  }
  localStorage.removeItem('matchupiq_user');
  return { success: true };
}
